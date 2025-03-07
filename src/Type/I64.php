<?php

namespace Oatmael\WasmPhp\Type;

class I64 implements ValueInterface {
    public function __construct(
        public readonly int $value
    )
    {
    }
    
    public function getUSize(): int { 
        return 8;
    }

    public function getValue() { 
        return $this->value;
    }
}
