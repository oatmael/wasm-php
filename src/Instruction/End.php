<?php

namespace Oatmael\WasmPhp\Instruction;

use Oatmael\WasmPhp\Execution\Store;

#[Opcode(StandardOpcode::end)]
class End implements InstructionInterface {
    public static function fromInput(string $input, int &$offset): InstructionInterface
    {
        return new self();
    }

    public function execute(array &$stack, array &$call_stack, Store $store)
    {
        // TODO: This is currently functionally equivalent to `return`, but `end` should only do so when the current block is a function.
        // This will need to handle blocks appropriately in the future.
        $store->popFrame($stack, $call_stack);
    }

}