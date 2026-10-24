<?php

namespace Oatmael\WasmPhp\Instruction\Simd;

use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Instruction\InstructionInterface;
use Oatmael\WasmPhp\Type\V128;
use Oatmael\WasmPhp\Util\Shape;

class I16x8SubSatU implements InstructionInterface {
    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self();
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        $right = array_pop($stack)->lanes(Shape::I16x8);
        $left = array_pop($stack)->lanes(Shape::I16x8);
        $stack[] = V128::fromLanes(Shape::I16x8, array_map(static fn ($a, $b) => max(0, min(65535, $a - $b)), $left, $right));
    }
}
