<?php

namespace Oatmael\WasmPhp\Type;

// A reference to a host value, or an internal reference converted with extern.convert_any
class ExternRef implements ValueInterface {
    public function __construct(
        public readonly mixed $value,
    )
    {
    }

    public function getValue()
    {
        return $this->value;
    }
}
