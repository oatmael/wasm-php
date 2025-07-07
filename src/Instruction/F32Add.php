<?php

namespace Oatmael\WasmPhp\Instruction;

use Exception;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Type\F32;

#[Opcode(StandardOpcode::f32_add)]
class F32Add implements InstructionInterface {
    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self();
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        $right = array_pop($stack);
        $left = array_pop($stack);

        if (!$left instanceof F32 || !$right instanceof F32) {
            throw new Exception('Invalid operand types for f32.add');
        }

        array_push($stack, new F32($left->getValue() + $right->getValue()));
    }
}