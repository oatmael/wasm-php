<?php

namespace Oatmael\WasmPhp\Instruction\Simd;

use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Instruction\InstructionInterface;
use Oatmael\WasmPhp\Type\V128;
use Oatmael\WasmPhp\Util\Floats;
use Oatmael\WasmPhp\Util\Shape;

// Relaxed: behaves as i32x4.trunc_sat_f32x4_s
class I32x4RelaxedTruncF32x4S implements InstructionInterface {
    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self();
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        $stack[] = V128::fromLanes(Shape::I32x4, array_map(static fn ($a) => Floats::toIntSaturated($a, 32, signed: true), array_pop($stack)->lanes(Shape::F32x4)));
    }
}
