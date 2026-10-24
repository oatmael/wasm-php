<?php

namespace Oatmael\WasmPhp\Instruction\Simd;

use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Instruction\InstructionInterface;
use Oatmael\WasmPhp\Type\V128;
use Oatmael\WasmPhp\Util\Floats;
use Oatmael\WasmPhp\Util\Shape;

class I32x4TruncSatF64x2UZero implements InstructionInterface {
    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self();
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        $lanes = array_map(static fn ($a) => Floats::toIntSaturated($a, 32, signed: false), array_pop($stack)->lanes(Shape::F64x2));
        $stack[] = V128::fromLanes(Shape::I32x4, [...$lanes, 0, 0]);
    }
}
