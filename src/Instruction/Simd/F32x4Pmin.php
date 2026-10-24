<?php

namespace Oatmael\WasmPhp\Instruction\Simd;

use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Instruction\InstructionInterface;
use Oatmael\WasmPhp\Type\V128;
use Oatmael\WasmPhp\Util\Shape;

// Pseudo-minimum: $b < $a ? $b : $a, which differs from min for NaNs and zeros
class F32x4Pmin implements InstructionInterface {
    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self();
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        $right_vector = array_pop($stack);
        $left_vector = array_pop($stack);
        $right = $right_vector->lanes(Shape::F32x4);
        $left = $left_vector->lanes(Shape::F32x4);
        $right_bits = $right_vector->lanes(Shape::I32x4);
        $result = $left_vector->lanes(Shape::I32x4);
        foreach ($result as $i => $lane) {
            if ($right[$i] < $left[$i]) {
                $result[$i] = $right_bits[$i];
            }
        }
        $stack[] = V128::fromLanes(Shape::I32x4, $result);
    }
}
