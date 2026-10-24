<?php

namespace Oatmael\WasmPhp\Instruction\Simd;

use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Instruction\InstructionInterface;
use Oatmael\WasmPhp\Type\V128;
use Oatmael\WasmPhp\Util\Shape;

class I64x2Shl implements InstructionInterface {
    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self();
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        $shift = array_pop($stack)->value & 63;
        $stack[] = V128::fromLanes(Shape::I64x2, array_map(static fn ($a) => $a << $shift, array_pop($stack)->lanes(Shape::I64x2)));
    }
}
