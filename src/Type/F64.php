<?php

namespace Oatmael\WasmPhp\Type;

class F64 implements ValueInterface {
    public function __construct(
        public readonly float $value
    )
    {
    }

    public static function fromBits(int $bits): self
    {
        return new self(unpack('e', pack('P', $bits))[1]);
    }

    public function getValue(): float
    {
        return $this->value;
    }

    // The 64-bit IEEE 754 representation, as a signed PHP int
    public function getBits(): int
    {
        return unpack('q', pack('e', $this->value))[1];
    }
}
