<?php

namespace Oatmael\WasmPhp\Type;

class TableType {
    public function __construct(
        public readonly RefType $element_type,
        public readonly int $min,
        public readonly ?int $max,
        // Tables indexed by i64 (table64)
        public readonly bool $is64 = false,
        // The constant expression each element is initialised to, for tables defined with one
        public readonly array $init = [],
    )
    {
    }
}
