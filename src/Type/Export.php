<?php

namespace Oatmael\WasmPhp\Type;

use Oatmael\WasmPhp\Util\ExportType;

class Export {
    public function __construct(
        public readonly string $name,
        public readonly ExportType $type,
        public readonly int $idx,
    )
    {
    }
}