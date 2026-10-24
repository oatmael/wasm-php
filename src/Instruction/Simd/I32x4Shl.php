<?php

namespace Oatmael\WasmPhp\Instruction\Simd;

use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Instruction\InstructionInterface;
use Oatmael\WasmPhp\Type\V128;
use Oatmael\WasmPhp\Util\Shape;

class I32x4Shl implements InstructionInterface {
    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self();
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        $shift = array_pop($stack)->value & 31;
        $stack[] = V128::fromLanes(Shape::I32x4, array_map(static fn ($a) => $a << $shift, array_pop($stack)->lanes(Shape::I32x4)));
    }
}
