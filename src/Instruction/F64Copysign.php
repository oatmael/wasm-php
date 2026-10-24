<?php

namespace Oatmael\WasmPhp\Instruction;

use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Type\F64;

// Operates on the raw bits, so NaN payloads are preserved
class F64Copysign implements InstructionInterface {
    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self();
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        $right = array_pop($stack);
        $left = array_pop($stack);
        $stack[] = F64::fromBits(($left->getBits() & PHP_INT_MAX) | ($right->getBits() & PHP_INT_MIN));
    }
}
