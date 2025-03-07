<?php

namespace Oatmael\WasmPhp\Type;

class F32 implements ValueInterface {
    public function __construct(
        public readonly float $value
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
