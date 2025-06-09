<?php

namespace Oatmael\WasmPhp\Instruction;

use Exception;
use Oatmael\WasmPhp\Execution\Store;

#[Opcode(StandardOpcode::else)]
class IElse implements InstructionInterface
{
    public static function fromInput(string $input, int &$offset): InstructionInterface
    {
        return new self();
    }

    public function execute(array &$stack, array &$call_stack, Store $store)
    {
        // This isn't the correct way to handle the else instruction per spec,
        // but in normal execution else should only be reached when the top of the control stack is an if instruction.
        $frame = end($call_stack);
        $control_stack_entry = array_pop($frame->control_stack);
        if ($control_stack_entry === null) {
            throw new Exception('Invalid control stack entry for else instruction');
        }

        $frame->program_counter = $control_stack_entry->break_target;
    }
}
