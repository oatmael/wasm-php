<?php

namespace Oatmael\WasmPhp\Type;

class ArrayRef implements ValueInterface {
    public function __construct(
        // Canonical type id, see TypeRegistry
        public readonly int $type_id,
        public array $elements,
    )
    {
    }

    public function getValue()
    {
        return $this->elements;
    }
}
