<?php

namespace Oatmael\WasmPhp\Type;

use Oatmael\WasmPhp\Util\ValueType;

class GlobalMutable {
    public function __construct(
        public readonly ValueType $type,
        public ValueInterface $value,
    )
    {
    }
}