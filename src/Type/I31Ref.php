<?php

namespace Oatmael\WasmPhp\Type;

// An unboxed 31-bit integer
class I31Ref implements ValueInterface {
    public readonly int $value;

    public function __construct(int $value)
    {
        $this->value = $value & 0x7FFFFFFF;
    }

    public function getValue(bool $signed = false): int
    {
        return $signed && ($this->value & 0x40000000) ? $this->value | (-1 << 31) : $this->value;
    }
}
