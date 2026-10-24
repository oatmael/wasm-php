<?php

namespace Oatmael\WasmPhp\Type;

use Oatmael\WasmPhp\Util\HeapType;

// A reference type, where the heap type is either abstract or a type index into the defining module's types
// https://webassembly.github.io/spec/core/syntax/types.html#reference-types
class RefType {
    public function __construct(
        public readonly bool $nullable,
        public readonly HeapType|int $heap_type,
    )
    {
    }

    public function defaultValue(): NullRef
    {
        return new NullRef();
    }

    public function label(): string
    {
        $heap_type = $this->heap_type instanceof HeapType ? strtolower($this->heap_type->name) : (string) $this->heap_type;
        return '(ref ' . ($this->nullable ? 'null ' : '') . $heap_type . ')';
    }
}
