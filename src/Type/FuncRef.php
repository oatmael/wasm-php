<?php

namespace Oatmael\WasmPhp\Type;

use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Import\HostFunction;

// A function instance, which is also the value of a reference to it. Wasm functions run in the store that
// defined them, so they can be called through imports and tables from other modules.
class FuncRef implements ValueInterface {
    public function __construct(
        public readonly Func $type,
        // Canonical type id, see TypeRegistry
        public readonly int $type_id,
        // The defining store for wasm functions, or the importing store for host functions
        public readonly Store $store,
        public readonly ?Code $code = null,
        public readonly ?HostFunction $host = null,
    )
    {
    }

    public function getValue()
    {
        return $this;
    }
}
