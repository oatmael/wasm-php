<?php

namespace Oatmael\WasmPhp\Instruction;

use Exception;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Type\F32;
use Oatmael\WasmPhp\Type\I32;

#[Opcode(StandardOpcode::f32_gt)]
class F32Gt implements InstructionInterface {
    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self();
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        $right = array_pop($stack);
        $left = array_pop($stack);

        if (!$left instanceof F32 || !$right instanceof F32) {
            throw new Exception('Invalid operand types for f32.gt');
        }

        array_push($stack, new I32($left->getValue() > $right->getValue() ? 1 : 0));
    }
}