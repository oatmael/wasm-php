<?php

namespace Oatmael\WasmPhp\Instruction;

use Oatmael\WasmPhp\Execution\Store;

class IElse implements InstructionInterface
{
    public static function fromInput(string $input, int &$offset): InstructionInterface
    {
        return new self();
    }

    public function execute(array &$stack, array &$call_stack, Store $store)
    {
        // Else is only reached at the end of an if's then branch, so it exits the if like a branch to its label
        $store->branch($stack, $call_stack, 0);
    }
}
