<?php

namespace Oatmael\WasmPhp\Instruction;

use Oatmael\WasmPhp\Execution\Store;

class ReturnCallRef extends CallRef {
    public function execute(array &$stack, array &$call_stack, Store $store)
    {
        $store->tailCall($stack, $call_stack, self::target($stack));
    }
}
