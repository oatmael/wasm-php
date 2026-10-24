<?php

namespace Oatmael\WasmPhp\Type;

class MemoryType {
    public function __construct(
        public readonly int $min,
        public readonly ?int $max,
        // Memories addressed by i64 (memory64)
        public readonly bool $is64 = false,
    )
    {
    }
}
