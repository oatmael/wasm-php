<?php

namespace Oatmael\WasmPhp\Instruction\Simd;

use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Instruction\InstructionInterface;
use Oatmael\WasmPhp\Type\V128;
use Oatmael\WasmPhp\Util\Shape;

class I32x4Mul implements InstructionInterface {
    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self();
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        $right = array_pop($stack)->lanes(Shape::I32x4, signed: true);
        $left = array_pop($stack)->lanes(Shape::I32x4, signed: true);
        $stack[] = V128::fromLanes(Shape::I32x4, array_map(static fn ($a, $b) => $a * $b, $left, $right));
    }
}
