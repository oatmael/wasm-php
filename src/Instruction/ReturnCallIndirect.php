<?php

namespace Oatmael\WasmPhp\Instruction;

use Oatmael\WasmPhp\Execution\Store;

class ReturnCallIndirect extends CallIndirect
{
    public function execute(array &$stack, array &$call_stack, Store $store)
    {
        $store->tailCall($stack, $call_stack, $this->target($stack, $store));
    }
}
