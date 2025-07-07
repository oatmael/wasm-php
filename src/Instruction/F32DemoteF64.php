<?php

namespace Oatmael\WasmPhp\Instruction;

use Exception;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Type\F32;
use Oatmael\WasmPhp\Type\F64;

#[Opcode(StandardOpcode::f32_demote_f64)]
class F32DemoteF64 implements InstructionInterface {
    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self();
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        $target = array_pop($stack);
        if (!($target instanceof F64)) {
            throw new Exception('Invalid operand types for f32.demote_f64');
        }

        $value = $target->getValue();
        array_push($stack, new F32($value));
    }
}
