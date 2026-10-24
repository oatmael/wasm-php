<?php

namespace Oatmael\WasmPhp\Type;

// An exception tag. Tags are compared by identity, so each instance is a distinct kind of exception.
class Tag {
    public function __construct(
        public readonly Func $type,
        // Canonical type id, see TypeRegistry
        public readonly int $type_id,
    )
    {
    }
}
