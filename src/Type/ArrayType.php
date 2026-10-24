<?php

namespace Oatmael\WasmPhp\Type;

class ArrayType {
    public function __construct(
        public readonly FieldType $field,
        public readonly array $supertypes = [],
        public readonly bool $final = true,
    )
    {
    }
}
