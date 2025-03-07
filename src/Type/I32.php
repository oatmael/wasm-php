<?php

namespace Oatmael\WasmPhp\Type;

class I32 implements ValueInterface {
    public function __construct(
        public readonly int $value
    )
    {
    }

    public function getUSize(): int { 
        return 4;
    }

    public function getValue() { 
        return $this->value;
    }
}
