<?php

namespace Oatmael\WasmPhp\Instruction\Simd;

use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Instruction\InstructionInterface;
use Oatmael\WasmPhp\Type\V128;
use Oatmael\WasmPhp\Util\Shape;

// Adds each pair of adjacent lanes, extended to twice the width
class I16x8ExtaddPairwiseI8x16U implements InstructionInterface {
    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self();
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        $stack[] = V128::fromLanes(Shape::I16x8, array_map(array_sum(...), array_chunk(array_pop($stack)->lanes(Shape::I8x16), 2)));
    }
}
