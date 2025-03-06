<?php

namespace Oatmael\WasmPhp\Type;

use Oatmael\WasmPhp\Util\ValueType;

class Local {
    public function __construct(
        public readonly int $type_count,
        public readonly ValueType $value_type,
    )
    {
    }
}