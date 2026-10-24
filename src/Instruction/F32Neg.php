<?php

namespace Oatmael\WasmPhp\Instruction;

use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Type\F32;

// Operates on the raw bits, so NaN payloads are preserved
class F32Neg implements InstructionInterface {
    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self();
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        $stack[] = F32::fromBits(array_pop($stack)->getBits() ^ 0x80000000);
    }
}
