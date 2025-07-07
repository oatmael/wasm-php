<?php

namespace Oatmael\WasmPhp\Instruction;

use Exception;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Type\F32;
use Oatmael\WasmPhp\Type\I64;

#[Opcode(StandardOpcode::f32_convert_i64_s)]
class F32ConvertI64S implements InstructionInterface {
    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self();
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        $target = array_pop($stack);
        if (!($target instanceof I64)) {
            throw new Exception('Invalid operand types for f32.convert_i64_s');
        }

        $value = $target->getValue();
        array_push($stack, new F32((float)$value));
    }
}