<?php

namespace Oatmael\WasmPhp\Instruction\Simd;

use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Instruction\InstructionInterface;
use Oatmael\WasmPhp\Type\V128;

// Relaxed: behaves as v128.bitselect
class I8x16RelaxedLaneselect implements InstructionInterface {
    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self();
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        $mask = array_pop($stack)->bytes;
        $right = array_pop($stack)->bytes;
        $left = array_pop($stack)->bytes;
        $stack[] = new V128(($left & $mask) | ($right & ~$mask));
    }
}
