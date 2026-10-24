<?php

namespace Oatmael\WasmPhp\Instruction;

use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Type\I32;

class I32Ctz implements InstructionInterface {
    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self();
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        $value = array_pop($stack)->value & 0xFFFFFFFF;
        if ($value === 0) {
            $stack[] = new I32(32);
            return;
        }

        $bits = decbin($value);
        $stack[] = new I32(strlen($bits) - 1 - strrpos($bits, '1'));
    }
}
