<?php

namespace Oatmael\WasmPhp\Type;

use Oatmael\WasmPhp\Util\ValueType;

// A global instance, which can be shared between modules through imports
class GlobalVariable {
    public function __construct(
        public readonly ValueType|RefType $type,
        public readonly bool $mutable,
        public ValueInterface $value,
    )
    {
    }
}
