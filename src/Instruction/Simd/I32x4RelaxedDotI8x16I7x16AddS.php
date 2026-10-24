<?php

namespace Oatmael\WasmPhp\Instruction\Simd;

use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Instruction\InstructionInterface;
use Oatmael\WasmPhp\Type\V128;
use Oatmael\WasmPhp\Util\Shape;

// Relaxed: treats both operands as signed, and wraps the sums
class I32x4RelaxedDotI8x16I7x16AddS implements InstructionInterface {
    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self();
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        $accumulators = array_pop($stack)->lanes(Shape::I32x4, signed: true);
        $right = array_pop($stack)->lanes(Shape::I8x16, signed: true);
        $left = array_pop($stack)->lanes(Shape::I8x16, signed: true);
        $products = array_map(static fn ($a, $b) => $a * $b, $left, $right);
        $stack[] = V128::fromLanes(Shape::I32x4, array_map(static fn (array $group, int $accumulator) => array_sum($group) + $accumulator, array_chunk($products, 4), $accumulators));
    }
}
