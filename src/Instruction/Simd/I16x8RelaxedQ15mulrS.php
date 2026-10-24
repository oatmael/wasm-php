<?php

namespace Oatmael\WasmPhp\Instruction\Simd;

use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Instruction\InstructionInterface;
use Oatmael\WasmPhp\Type\V128;
use Oatmael\WasmPhp\Util\Shape;

// Relaxed: behaves as i16x8.q15mulr_sat_s
class I16x8RelaxedQ15mulrS implements InstructionInterface {
    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self();
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        $right = array_pop($stack)->lanes(Shape::I16x8, signed: true);
        $left = array_pop($stack)->lanes(Shape::I16x8, signed: true);
        $stack[] = V128::fromLanes(Shape::I16x8, array_map(static fn ($a, $b) => max(-32768, min(32767, ($a * $b + 0x4000) >> 15)), $left, $right));
    }
}
