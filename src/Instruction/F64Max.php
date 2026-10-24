<?php

namespace Oatmael\WasmPhp\Instruction;

use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Type\F64;
use Oatmael\WasmPhp\Util\Floats;

class F64Max implements InstructionInterface {
    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self();
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        $right = array_pop($stack);
        $left = array_pop($stack);
        $stack[] = new F64(Floats::max($left->value, $right->value));
    }
}
