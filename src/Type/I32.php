<?php

namespace Oatmael\WasmPhp\Type;

class I32 implements ValueInterface {
    public function __construct(
        // Note: while I32 is backed by a PHP int, in most cases only the lower 32 bits are used.
        public readonly int $value
    )
    {
    }

    public function getUSize(): int
    {
        return 4;
    }

    public function getValue()
    {
        if ($this->value & 0x80000000) {
            return $this->value | (-1 << 32);
        }

        return $this->value & 0xFFFFFFFF;
    }

    public function toUnsigned(): self
    {
        return new self($this->value >= 0 ? $this->value : (2 ** 32) + $this->value);
    }
}
