<?php

namespace Oatmael\WasmPhp\Type;

// A host value converted into the any hierarchy with any.convert_extern
class HostRef implements ValueInterface {
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
