<?php

namespace Oatmael\WasmPhp\Instruction;

use Oatmael\WasmPhp\Execution\Store;

class Select implements InstructionInterface {
    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self();
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        $condition = array_pop($stack);
        $right = array_pop($stack);
        $left = array_pop($stack);

        $stack[] = $condition->getValue() !== 0 ? $left : $right;
    }
}
