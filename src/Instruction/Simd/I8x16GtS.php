<?php

namespace Oatmael\WasmPhp\Instruction\Simd;

use Exception;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Instruction\InstructionInterface;
use Oatmael\WasmPhp\Instruction\SimdOpcode;
use Oatmael\WasmPhp\Instruction\Opcode;

#[Opcode(SimdOpcode::i8x16_gt_s)]
class I8x16GtS implements InstructionInterface {
    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self();
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        throw new Exception('Not implemented: i8x16.gt_s opcode');
    }
}