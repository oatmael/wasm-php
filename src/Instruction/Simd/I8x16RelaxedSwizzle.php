<?php

namespace Oatmael\WasmPhp\Instruction\Simd;

use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Instruction\InstructionInterface;
use Oatmael\WasmPhp\Type\V128;

// Relaxed: behaves as i8x16.swizzle
class I8x16RelaxedSwizzle implements InstructionInterface {
    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self();
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        $indices = array_pop($stack)->bytes;
        $bytes = array_pop($stack)->bytes;

        $result = '';
        for ($i = 0; $i < 16; $i++) {
            $index = ord($indices[$i]);
            $result .= $index < 16 ? $bytes[$index] : "\0";
        }
        $stack[] = new V128($result);
    }
}
