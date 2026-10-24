<?php

namespace Oatmael\WasmPhp\Instruction;

use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Type\I31Ref;
use Oatmael\WasmPhp\Type\I32;
use Oatmael\WasmPhp\Type\NullRef;

// References are equal when they're both null, the same object, or i31s with the same value
class RefEq implements InstructionInterface {
    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self();
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        $right = array_pop($stack);
        $left = array_pop($stack);

        $equal = $left === $right ||
            ($left instanceof NullRef && $right instanceof NullRef) ||
            ($left instanceof I31Ref && $right instanceof I31Ref && $left->value === $right->value);

        $stack[] = new I32($equal ? 1 : 0);
    }
}
