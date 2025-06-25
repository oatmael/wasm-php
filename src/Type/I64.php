<?php

namespace Oatmael\WasmPhp\Type;

use Exception;

class I64 implements ValueInterface {
    public function __construct(
        public readonly int $value
    )
    {
        if (PHP_INT_SIZE === 4) {
            throw new Exception('I64 is not supported on 32-bit systems');
        }
    }

    public function getUSize(): int
    {
        return 8;
    }

    public function getValue()
    {
        return $this->value;
    }
}
