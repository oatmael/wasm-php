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

    public function toUnsigned(): self
    {
        // TODO: this isn't right, since PHP only has signed 64-bit integers, and will convert to a float on overflow.
        return new self((int)($this->value >= 0 ? $this->value : (2 ** 64) + $this->value));
    }
}
