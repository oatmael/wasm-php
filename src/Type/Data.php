<?php

namespace Oatmael\WasmPhp\Type;

class Data {

    public function __construct(
        public readonly int $memory_idx,
        public readonly int $offset,
        public readonly array $init
    )
    {
    }
}