<?php

namespace Oatmael\WasmPhp\Type;

class Func {
    public function __construct(
        public readonly array $params,
        public readonly array $results,
    )
    {
    }
}