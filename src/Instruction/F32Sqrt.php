<?php

namespace Oatmael\WasmPhp\Instruction;

use Exception;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Type\F32;

#[Opcode(StandardOpcode::f32_sqrt)]
class F32Sqrt implements InstructionInterface {
    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self();
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        $value = array_pop($stack);
        if (!$value instanceof F32) {
            throw new Exception('Invalid operand types for f32.sqrt');
        }

        array_push($stack, new F32(sqrt($value->getValue())));
    }
}
