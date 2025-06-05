<?php

namespace Oatmael\WasmPhp\Instruction;

use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Execution\Frame;
use Oatmael\WasmPhp\Execution\ControlStackEntry;

#[Opcode(StandardOpcode::end)]
class End implements InstructionInterface {
    public static function fromInput(string $input, int &$offset): InstructionInterface
    {
        return new self();
    }

    public function execute(array &$stack, array &$call_stack, Store $store)
    {
        /** @var Frame $frame */
        $frame = end($call_stack);
        if (count($frame->control_stack)) {
            /** @var ControlStackEntry */
            $control_stack_entry = array_pop($frame->control_stack);
        } else {
            $store->popFrame($stack, $call_stack);
        }
    }

}