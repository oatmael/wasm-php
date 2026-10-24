<?php

namespace Oatmael\WasmPhp\Instruction\Simd;

use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Instruction\InstructionInterface;
use Oatmael\WasmPhp\Type\V128;
use Oatmael\WasmPhp\Util\Shape;

// Pseudo-maximum: $a < $b ? $b : $a, which differs from max for NaNs and zeros
class F64x2Pmax implements InstructionInterface {
    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self();
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        $right_vector = array_pop($stack);
        $left_vector = array_pop($stack);
        $right = $right_vector->lanes(Shape::F64x2);
        $left = $left_vector->lanes(Shape::F64x2);
        $right_bits = $right_vector->lanes(Shape::I64x2);
        $result = $left_vector->lanes(Shape::I64x2);
        foreach ($result as $i => $lane) {
            if ($left[$i] < $right[$i]) {
                $result[$i] = $right_bits[$i];
            }
        }
        $stack[] = V128::fromLanes(Shape::I64x2, $result);
    }
}
