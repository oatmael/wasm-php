<?php

namespace Oatmael\WasmPhp\Instruction;

use Exception;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Type\F32;

#[Opcode(StandardOpcode::f32_trunc)]
class F32Trunc implements InstructionInterface {
    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self();
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        $target = array_pop($stack);
        if (!$target instanceof F32) {
            throw new Exception('Invalid operand types for f32.trunc');
        }

        array_push($stack, new F32(round($target->getValue(), 0, PHP_ROUND_HALF_DOWN)));
    }
}