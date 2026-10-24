<?php

namespace Oatmael\WasmPhp\Util;

// Storage types that are only valid in struct and array fields, read and written as an i32
enum PackedType: int {
    case I8  = 0x78;
    case I16 = 0x77;

    public function bits(): int
    {
        return $this === self::I8 ? 8 : 16;
    }

    public function label(): string
    {
        return strtolower($this->name);
    }
}
