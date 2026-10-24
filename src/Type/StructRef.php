<?php

namespace Oatmael\WasmPhp\Type;

class StructRef implements ValueInterface {
    public function __construct(
        // Canonical type id, see TypeRegistry
        public readonly int $type_id,
        public array $fields,
    )
    {
    }

    public function getValue()
    {
        return $this->fields;
    }
}
