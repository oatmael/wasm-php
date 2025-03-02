<?php

namespace Oatmael\WasmPhp\Type;

class Extern {
    public function __construct(
        public readonly array $params,
        public readonly array $results,
    )
    {
    }
}