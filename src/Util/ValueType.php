<?php

namespace Oatmael\WasmPhp\Util;

use Exception;
use Oatmael\WasmPhp\Type\F32;
use Oatmael\WasmPhp\Type\F64;
use Oatmael\WasmPhp\Type\I32;
use Oatmael\WasmPhp\Type\I64;

enum ValueType: int {
    case I32       = 0x7F;
    case I64       = 0x7E;
    case F32       = 0x7D;
    case F64       = 0x7C;
    case VEC       = 0x7B;
    case FUNCREF   = 0x70;
    case EXTERNREF = 0x6F;
    case VOID      = 0x40;

    // The class that holds values of this type at runtime
    public function valueClass(): string
    {
        return match ($this) {
            self::I32 => I32::class,
            self::I64 => I64::class,
            self::F32 => F32::class,
            self::F64 => F64::class,
            default   => throw new Exception('Unsupported value type: ' . $this->name),
        };
    }
}
