<?php

namespace Oatmael\WasmPhp\Instruction\Simd;

use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Instruction\InstructionInterface;
use Oatmael\WasmPhp\Type\I32;
use Oatmael\WasmPhp\Util\Shape;

// The sign bit of each lane
class I8x16Bitmask implements InstructionInterface {
    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self();
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        $mask = 0;
        foreach (array_pop($stack)->lanes(Shape::I8x16, signed: true) as $i => $lane) {
            $mask |= ($lane < 0 ? 1 : 0) << $i;
        }
        $stack[] = new I32($mask);
    }
}
