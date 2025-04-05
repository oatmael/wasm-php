<?php

namespace Oatmael\WasmPhp\Type;

use Oatmael\WasmPhp\Util\ImportType;

class Import {
    public function __construct(
        public readonly string $module,
        public readonly string $field,
        public readonly ImportType $type,
        public readonly int $idx,
    )
    {
    }
}