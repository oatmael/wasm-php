<?php

namespace Oatmael\WasmPhp\Type;

class Code {
    public function __construct(
        public readonly array $locals,
        public readonly array $instructions,
    )
    {
    }
}