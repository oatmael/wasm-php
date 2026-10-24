<?php

namespace Oatmael\WasmPhp\Type;

use Oatmael\WasmPhp\Util\SegmentMode;

class Element {
    public function __construct(
        public readonly SegmentMode $mode,
        public readonly RefType $type,
        // A constant expression for each element
        public readonly array $init,
        // Where active segments are copied to: a table index and a constant expression for the offset
        public readonly int $table_idx = 0,
        public readonly array $offset = [],
    )
    {
    }
}
