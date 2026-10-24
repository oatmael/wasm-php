<?php

namespace Oatmael\WasmPhp\Type;

// PHP floats are doubles, so the value is rounded to single precision when it's created.
// Values read from raw bits (loads and reinterprets) keep those bits, so NaN payloads survive being moved around.
class F32 implements ValueInterface {
    public readonly float $value;
    private ?int $bits = null;

    public function __construct(float $value)
    {
        $this->value = unpack('g', pack('g', $value))[1];
    }

    public static function fromBits(int $bits): self
    {
        $bits &= 0xFFFFFFFF;
        $f32 = new self(unpack('g', pack('V', $bits))[1]);
        $f32->bits = $bits;

        return $f32;
    }

    public function getValue(): float
    {
        return $this->value;
    }

    // The unsigned 32-bit IEEE 754 representation
    public function getBits(): int
    {
        return $this->bits ?? unpack('V', pack('g', $this->value))[1];
    }
}
