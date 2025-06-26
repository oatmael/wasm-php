<?php

namespace Oatmael\WasmPhp\Instruction;

use Exception;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Type\I32;

#[Opcode(StandardOpcode::i32_eq)]
class I32Eq implements InstructionInterface {
    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self();
    }

    public function execute(array &$stack, array &$call_stack, Store $store)
    {
        $right = array_pop($stack);
        $left = array_pop($stack);
        if (!($left instanceof I32) || !($right instanceof I32)) {
            throw new Exception('Invalid stack params for i32.eq opcode');
        }

        array_push($stack, new I32($left->getValue() === $right->getValue() ? 1 : 0));
    }
}