<?php

namespace Oatmael\WasmPhp\Type;

class Import {
    public function __construct(
        public readonly string $module,
        public readonly string $field,
        public readonly int $function_idx,
    )
    {
    }
}