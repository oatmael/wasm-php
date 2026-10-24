<?php

namespace Oatmael\WasmPhp\Type;

use Oatmael\WasmPhp\Util\ImportType;

class Import {
    public function __construct(
        public readonly string $module,
        public readonly string $field,
        public readonly ImportType $type,
        // A type index for functions and tags, otherwise the type of the table, memory or global
        public readonly int|TableType|MemoryType|GlobalType $descriptor,
    )
    {
    }
}
