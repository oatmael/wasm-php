<?php

namespace Oatmael\WasmPhp\Type;

use Oatmael\WasmPhp\Util\ValueType;

class Table {
    public function __construct(
        public readonly ValueType $element_type,
        public readonly int $min,
        public readonly ?int $max,
    )
    {
    }
}