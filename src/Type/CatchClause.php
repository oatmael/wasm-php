<?php

namespace Oatmael\WasmPhp\Type;

// A try_table handler: catches exceptions with a tag (or any tag), and branches to a label with the exception's
// values, followed by the exception itself for the _ref variants
class CatchClause {
    public function __construct(
        public readonly ?int $tag_idx,
        public readonly bool $ref,
        public readonly int $label,
    )
    {
    }
}
