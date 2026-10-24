<?php

namespace Oatmael\WasmPhp\Util;

use Exception;
use Oatmael\WasmPhp\Type\F32;
use Oatmael\WasmPhp\Type\F64;
use Oatmael\WasmPhp\Type\I32;
use Oatmael\WasmPhp\Type\I64;
use Oatmael\WasmPhp\Type\V128;
use Oatmael\WasmPhp\Type\ValueInterface;

// Number and vector types. Reference types are RefType objects, as they can refer to a type index.
enum ValueType: int {
    case I32  = 0x7F;
    case I64  = 0x7E;
    case F32  = 0x7D;
    case F64  = 0x7C;
    case V128 = 0x7B;
    // Only valid as a block type
    case VOID = 0x40;

    // The class that holds values of this type at runtime
    public function valueClass(): string
    {
        return match ($this) {
            self::I32  => I32::class,
            self::I64  => I64::class,
            self::F32  => F32::class,
            self::F64  => F64::class,
            self::V128 => V128::class,
            default    => throw new Exception('Unsupported value type: ' . $this->name),
        };
    }

    public function defaultValue(): ValueInterface
    {
        return match ($this) {
            self::I32  => new I32(0),
            self::I64  => new I64(0),
            self::F32  => new F32(0.0),
            self::F64  => new F64(0.0),
            self::V128 => new V128(str_repeat("\0", 16)),
            default    => throw new Exception('Unsupported value type: ' . $this->name),
        };
    }

    public function label(): string
    {
        return strtolower($this->name);
    }
}
