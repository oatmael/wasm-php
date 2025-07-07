<?php

namespace Oatmael\WasmPhp\Type;

class F32 implements ValueInterface {
    public function __construct(
        public readonly float $value
    )
    {
    }

    public function getUSize(): int
    {
        return 4;
    }

    public function getValue()
    {
        if (PHP_INT_SIZE === 4) {
            return $this->value;
        }

        // Pack the 64-bit float to get its binary representation
        $f64_bytes = pack('e', $this->value);

        // Extract the 64-bit components
        $f64_bits = unpack('q', $f64_bytes)[1]; // q = 64-bit signed little-endian

        // Extract sign, exponent, and mantissa from 64-bit float
        $sign = ($f64_bits >> 63) & 1;
        $exponent = ($f64_bits >> 52) & 0x7FF;
        $mantissa = $f64_bits & 0xFFFFFFFFFFFFF;

        // Handle special cases
        if ($exponent == 0x7FF) {
            // NaN or Infinity
            if ($mantissa == 0) {
                // Infinity
                $f32_exponent = 0xFF;
                $f32_mantissa = 0;
            } else {
                // NaN
                $f32_exponent = 0xFF;
                $f32_mantissa = 0x400000; // Quiet NaN
            }
        } else if ($exponent == 0) {
            // Denormalized number
            $f32_exponent = 0;
            $f32_mantissa = 0;
        } else {
            // Normal number
            // Adjust exponent bias: 64-bit uses 1023, 32-bit uses 127
            $f32_exponent = $exponent - 1023 + 127;

            // Truncate mantissa from 52 bits to 23 bits
            $f32_mantissa = ($mantissa >> 29) & 0x7FFFFF;

            // Handle rounding (IEEE 754 round to nearest, ties to even)
            $rounding_bit = ($mantissa >> 28) & 1;
            $sticky_bit = ($mantissa >> 27) & 0x1FF; // All bits below the rounding bit
            $lsb = $f32_mantissa & 1;

            // Round up if:
            // 1. Rounding bit is 1 AND (sticky bit is non-zero OR LSB is 1)
            // 2. This implements "round to nearest, ties to even"
            if ($rounding_bit && ($sticky_bit != 0 || $lsb)) {
                $f32_mantissa++;
                if ($f32_mantissa & 0x800000) {
                    $f32_mantissa = 0;
                    $f32_exponent++;
                }
            }
        }

        // Reassemble 32-bit float
        $f32_bits = ($sign << 31) | ($f32_exponent << 23) | $f32_mantissa;
        $f32_bytes = pack('V', $f32_bits);

        // Unpack as 32-bit float
        $value = unpack('g', $f32_bytes)[1];

        return $value;
    }
}
