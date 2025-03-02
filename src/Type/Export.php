<?php

namespace Oatmael\WasmPhp\Type;

class Export {
    public function __construct(
        public readonly string $name,
        public readonly int $function_idx,
    )
    {
    }
}