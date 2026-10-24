<?php

namespace Oatmael\WasmPhp\Util;

use Oatmael\WasmPhp\Exception\BadIntegerCastException;

// Float operations whose wasm semantics differ from PHP's built-ins, shared by the scalar and SIMD instructions
// https://webassembly.github.io/spec/core/exec/numerics.html#floating-point-operations
class Floats
{
    // NaN if either operand is NaN, and -0 is less than +0
    public static function min(float $left, float $right): float
    {
        if (is_nan($left) || is_nan($right)) {
            return NAN;
        }

        if ($left == $right) {
            return self::isNegative($left) ? $left : $right;
        }

        return $left < $right ? $left : $right;
    }

    public static function max(float $left, float $right): float
    {
        if (is_nan($left) || is_nan($right)) {
            return NAN;
        }

        if ($left == $right) {
            return self::isNegative($left) ? $right : $left;
        }

        return $left > $right ? $left : $right;
    }

    // Round to the nearest integer, with ties to even, keeping the sign of zero
    public static function nearest(float $value): float
    {
        // Doubles of 2^52 and above are already integers
        if (is_nan($value)) {
            return $value + 0.0;
        }
        if (abs($value) >= 4503599627370496.0) {
            return $value;
        }

        $floor = floor($value);
        $fraction = $value - $floor;
        $rounded = match (true) {
            $fraction > 0.5 => $floor + 1,
            $fraction < 0.5 => $floor,
            default         => fmod($floor, 2.0) == 0.0 ? $floor : $floor + 1,
        };

        return $rounded == 0.0 ? self::withSign(0.0, $value) : $rounded;
    }

    public static function trunc(float $value): float
    {
        return $value < 0 ? ceil($value) : self::floor($value);
    }

    // PHP returns NaNs from ceil and floor unchanged, but wasm requires signalling NaNs to be quieted
    public static function ceil(float $value): float
    {
        return is_nan($value) ? $value + 0.0 : ceil($value);
    }

    public static function floor(float $value): float
    {
        return is_nan($value) ? $value + 0.0 : floor($value);
    }

    // Whether the sign bit is set, including for -0 and NaNs
    public static function isNegative(float $value): bool
    {
        return (bool) (unpack('C', pack('E', $value))[1] & 0x80);
    }

    public static function withSign(float $magnitude, float $sign): float
    {
        return self::isNegative($magnitude) === self::isNegative($sign) ? $magnitude : -$magnitude;
    }

    // Truncate towards zero and convert to a $bits wide integer, trapping if it doesn't fit. Unsigned i64 results
    // above 2^63 wrap negative, as they don't fit in a PHP int.
    public static function toInt(float $value, int $bits, bool $signed): int
    {
        if (is_nan($value)) {
            throw new BadIntegerCastException('invalid conversion to integer');
        }

        $value = self::trunc($value);
        [$min, $limit] = self::intRange($bits, $signed);
        if ($value < $min || $value >= $limit) {
            throw new BadIntegerCastException('integer overflow');
        }

        return self::wrapToInt($value);
    }

    // As toInt, but NaN converts to 0 and values outside the range saturate
    public static function toIntSaturated(float $value, int $bits, bool $signed): int
    {
        $value = self::trunc($value);
        [$min, $limit] = self::intRange($bits, $signed);
        return match (true) {
            is_nan($value)    => 0,
            $value < $min     => (int) $min,
            $value >= $limit  => $bits === 64 ? ($signed ? PHP_INT_MAX : -1) : (int) $limit - 1,
            default           => self::wrapToInt($value),
        };
    }

    // Convert an integer to the nearest float. Unsigned i64 values above 2^63 are passed as negative PHP ints.
    // Converting to f32 through a double would round twice, so large values are first narrowed to 53 bits with a
    // sticky bit (round to odd), which a single rounding to f32 then rounds correctly.
    public static function fromInt(int $value, bool $unsigned, bool $to_f32): float
    {
        $negative = !$unsigned && $value < 0;
        if ($value === PHP_INT_MIN && $negative) {
            return -9223372036854775808.0;
        }

        // The magnitude as an unsigned 64-bit pattern
        $magnitude = $negative ? -$value : $value;
        $limit = $to_f32 ? 1 << 53 : PHP_INT_MAX;

        $shift = 0;
        $sticky = 0;
        while ($magnitude < 0 || $magnitude >= $limit) {
            $sticky |= $magnitude & 1;
            $magnitude = ($magnitude >> 1) & PHP_INT_MAX;
            $shift++;
        }

        $result = (float) ($magnitude | $sticky) * (2 ** $shift);
        return $negative ? -$result : $result;
    }

    // The smallest value of an integer type, and the power of two above its largest, which are exact as floats
    protected static function intRange(int $bits, bool $signed): array
    {
        return $signed ? [-(2.0 ** ($bits - 1)), 2.0 ** ($bits - 1)] : [0.0, 2.0 ** $bits];
    }

    protected static function wrapToInt(float $value): int
    {
        // Values of 2^63 and above don't fit in a PHP int, so shift them down and set the sign bit to wrap them
        return $value >= 9223372036854775808.0 ? (int) ($value - 9223372036854775808.0) | PHP_INT_MIN : (int) $value;
    }
}
