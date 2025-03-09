<?php

namespace Oatmael\WasmPhp\Type;

use Oatmael\WasmPhp\Util\ValueType;

class GlobalImmutable {
    public function __construct(
        public readonly ValueType $type,
        public readonly ValueInterface $value,
    )
    {
    }
}