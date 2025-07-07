<?php

namespace Oatmael\WasmPhp\Instruction;

use Exception;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Type\F32;
use Oatmael\WasmPhp\Type\I32;

#[Opcode(StandardOpcode::f32_reinterpret_i32)]
class F32ReinterpretI32 implements InstructionInterface {
    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self();
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        $value = array_pop($stack);
        if (!($value instanceof I32)) {
            throw new Exception('Invalid operand types for f32.reinterpret_i32');
        }

        $reinterpret = pack('V', $value->getValue());
        $reinterpret = unpack('g', $reinterpret)[1];
        array_push($stack, new F32($reinterpret));
    }
}