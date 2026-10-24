<?php

namespace Oatmael\WasmPhp\Instruction\Simd;

use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Instruction\InstructionInterface;
use Oatmael\WasmPhp\Type\V128;
use Oatmael\WasmPhp\Util\Shape;

// Multiplies the lanes, and adds each pair of adjacent products
class I32x4DotI16x8S implements InstructionInterface {
    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self();
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        $right = array_pop($stack)->lanes(Shape::I16x8, signed: true);
        $left = array_pop($stack)->lanes(Shape::I16x8, signed: true);
        $products = array_map(static fn ($a, $b) => $a * $b, $left, $right);
        $stack[] = V128::fromLanes(Shape::I32x4, array_map(array_sum(...), array_chunk($products, 2)));
    }
}
