<?php

namespace Oatmael\WasmPhp\Instruction;

use Exception;
use Oatmael\WasmPhp\Execution\Store;

#[Opcode(StandardOpcode::f64_ne)]
class F64Ne implements InstructionInterface {
    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self();
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        throw new Exception('Not implemented: f64.ne opcode');
    }
}