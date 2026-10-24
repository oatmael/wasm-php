<?php

namespace Oatmael\WasmPhp\Type;

use Brick\Math\BigInteger;
use Exception;

class I64 implements ValueInterface {
    public function __construct(
        public readonly int $value
    )
    {
        if (PHP_INT_SIZE === 4) {
            throw new Exception('I64 is not supported on 32-bit systems');
        }
    }

    public function getValue()
    {
        return $this->value;
    }

    // Wrapping arithmetic on PHP ints, which turn into floats when they overflow
    public static function add(int $left, int $right): int
    {
        $result = $left + $right;
        return is_int($result) ? $result : self::fromBigInteger(BigInteger::of($left)->plus($right))->value;
    }

    public static function sub(int $left, int $right): int
    {
        $result = $left - $right;
        return is_int($result) ? $result : self::fromBigInteger(BigInteger::of($left)->minus($right))->value;
    }

    public static function mul(int $left, int $right): int
    {
        $result = $left * $right;
        return is_int($result) ? $result : self::fromBigInteger(BigInteger::of($left)->multipliedBy($right))->value;
    }

    // PHP ints are signed 64-bit, so the unsigned view (0 to 2^64 - 1) needs arbitrary precision
    public function toBigInteger(bool $unsigned = false): BigInteger
    {
        $value = BigInteger::of($this->value);
        return $unsigned && $this->value < 0 ? $value->plus(self::modulus()) : $value;
    }

    // Wrap any integer into the i64 range (modulo 2^64), as wasm integer arithmetic does
    public static function fromBigInteger(BigInteger $value): self
    {
        $value = $value->mod(self::modulus());
        if ($value->isGreaterThan(PHP_INT_MAX)) {
            $value = $value->minus(self::modulus());
        }

        return new self($value->toInt());
    }

    protected static function modulus(): BigInteger
    {
        return BigInteger::one()->shiftedLeft(64);
    }
}
