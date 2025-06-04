<?php

namespace Oatmael\WasmPhp\Instruction;

use Exception;
use Oatmael\WasmPhp\Execution\Store;

#[Opcode(StandardOpcode::f32_reinterpret_i32)]
class F32ReinterpretI32 implements InstructionInterface {
    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self();
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        throw new Exception('Not implemented: f32.reinterpret_i32 opcode');
    }
}