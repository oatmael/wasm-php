<?php

namespace Oatmael\WasmPhp\Type;

class StructType {
    public function __construct(
        /** @var FieldType[] */
        public readonly array $fields,
        public readonly array $supertypes = [],
        public readonly bool $final = true,
    )
    {
    }
}
