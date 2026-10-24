<?php

namespace Oatmael\WasmPhp\Type;

use Oatmael\WasmPhp\Util\PackedType;
use Oatmael\WasmPhp\Util\ValueType;

// A struct field or array element. Packed fields hold an i32, wrapped to their width.
class FieldType {
    public function __construct(
        public readonly ValueType|RefType|PackedType $storage,
        public readonly bool $mutable,
    )
    {
    }

    public function defaultValue(): ValueInterface
    {
        return $this->storage instanceof PackedType ? new I32(0) : $this->storage->defaultValue();
    }

    // Wrap a value to the field's width as it is stored
    public function pack(ValueInterface $value): ValueInterface
    {
        return $this->storage instanceof PackedType ? new I32($value->value & ((1 << $this->storage->bits()) - 1)) : $value;
    }

    // The size of the field in bytes, as stored in a data segment
    public function size(): int
    {
        return match ($this->storage) {
            PackedType::I8                => 1,
            PackedType::I16               => 2,
            ValueType::I32, ValueType::F32 => 4,
            ValueType::I64, ValueType::F64 => 8,
            ValueType::V128               => 16,
        };
    }

    // Decode a value from its little-endian bytes, for array.new_data and array.init_data
    public function fromBytes(string $bytes): ValueInterface
    {
        return match ($this->storage) {
            PackedType::I8  => new I32(ord($bytes)),
            PackedType::I16 => new I32(unpack('v', $bytes)[1]),
            ValueType::I32  => new I32(unpack('V', $bytes)[1]),
            ValueType::I64  => new I64(unpack('P', $bytes)[1]),
            ValueType::F32  => F32::fromBits(unpack('V', $bytes)[1]),
            ValueType::F64  => F64::fromBits(unpack('P', $bytes)[1]),
            ValueType::V128 => new V128($bytes),
        };
    }

    // Read a stored value, sign or zero extending packed fields
    public function unpack(ValueInterface $value, bool $signed = false): ValueInterface
    {
        if (!($this->storage instanceof PackedType) || !$signed) {
            return $value;
        }

        $shift = 64 - $this->storage->bits();
        return new I32(($value->value << $shift) >> $shift);
    }
}
