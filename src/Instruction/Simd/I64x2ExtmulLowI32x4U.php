<?php

namespace Oatmael\WasmPhp\Instruction\Simd;

use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Instruction\InstructionInterface;
use Oatmael\WasmPhp\Type\I64;
use Oatmael\WasmPhp\Type\V128;
use Oatmael\WasmPhp\Util\Shape;

// Multiplies the extended lanes
class I64x2ExtmulLowI32x4U implements InstructionInterface {
    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self();
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        $right = array_slice(array_pop($stack)->lanes(Shape::I32x4), 0, 2);
        $left = array_slice(array_pop($stack)->lanes(Shape::I32x4), 0, 2);
        $stack[] = V128::fromLanes(Shape::I64x2, array_map(static fn ($a, $b) => I64::mul($a, $b), $left, $right));
    }
}
