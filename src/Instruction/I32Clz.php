<?php

namespace Oatmael\WasmPhp\Instruction;

use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Type\I32;

class I32Clz implements InstructionInterface {
    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self();
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        $value = array_pop($stack)->value & 0xFFFFFFFF;
        $stack[] = new I32($value === 0 ? 32 : 32 - strlen(decbin($value)));
    }
}
