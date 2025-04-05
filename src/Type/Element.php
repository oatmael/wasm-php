<?php

namespace Oatmael\WasmPhp\Type;

class Element {

    public function __construct(
        public readonly int $table_idx,
        public readonly int $offset,
        public readonly array $init
    )
    {
    }
}