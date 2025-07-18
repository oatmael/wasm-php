<?php

namespace Oatmael\WasmPhp\Instruction;

use Exception;
use Oatmael\WasmPhp\Execution\Store;

#[Opcode(StandardOpcode::select)]
class Select implements InstructionInterface {
    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self();
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        $condition = array_pop($stack);
        $left = array_pop($stack);
        $right = array_pop($stack);

        if (!$condition === null || $left === null || $right === null) {
            throw new Exception('Invalid stack state for select opcode');
        }

        $condition->value ? array_push($stack, $left) : array_push($stack, $right);
    }
}