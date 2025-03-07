<?php

namespace Oatmael\WasmPhp\Type;

class F64 implements ValueInterface {
    public function __construct(
        public readonly float $value
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
