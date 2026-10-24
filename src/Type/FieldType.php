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
