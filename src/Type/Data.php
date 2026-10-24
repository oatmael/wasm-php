<?php

namespace Oatmael\WasmPhp\Type;

use Oatmael\WasmPhp\Util\SegmentMode;

class Data {
    public function __construct(
        public readonly SegmentMode $mode,
        public readonly string $init,
        // Where active segments are copied to: a memory index and a constant expression for the offset
        public readonly int $memory_idx = 0,
        public readonly array $offset = [],
    )
    {
    }
}
