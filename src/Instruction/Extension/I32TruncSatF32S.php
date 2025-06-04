<?php

namespace Oatmael\WasmPhp\Instruction\Extension;

use Exception;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Instruction\InstructionInterface;
use Oatmael\WasmPhp\Instruction\Opcode;
use Oatmael\WasmPhp\Instruction\ExtensionOpcode;

#[Opcode(ExtensionOpcode::i32_trunc_sat_f32_s)]
class I32TruncSatF32S implements InstructionInterface {
    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self();
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        throw new Exception('Not implemented: i32.trunc_sat_f32_s opcode');
    }
}