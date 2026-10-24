<?php

namespace Oatmael\WasmPhp\Instruction\Simd;

use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Instruction\InstructionInterface;
use Oatmael\WasmPhp\Type\V128;
use Oatmael\WasmPhp\Util\Shape;

class I8x16NarrowI16x8U implements InstructionInterface {
    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self();
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        $right = array_pop($stack)->lanes(Shape::I16x8, signed: true);
        $left = array_pop($stack)->lanes(Shape::I16x8, signed: true);
        $stack[] = V128::fromLanes(Shape::I8x16, array_map(static fn ($a) => max(0, min(255, $a)), [...$left, ...$right]));
    }
}
