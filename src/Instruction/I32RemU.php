<?php

namespace Oatmael\WasmPhp\Instruction;

use Exception;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Type\I32;

#[Opcode(StandardOpcode::i32_rem_u)]
class I32RemU implements InstructionInterface {
    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self();
    }

    public function execute(array &$stack, array &$call_stack, Store $store)
    {
        $right = array_pop($stack);
        $left = array_pop($stack);

        if (!$left instanceof I32 || !$right instanceof I32) {
            throw new Exception('Invalid operand types for i32.rem_u');
        }

        $left = $left->toUnsigned();
        $right = $right->toUnsigned();

        array_push($stack, new I32($left->value % $right->value));
    }
}