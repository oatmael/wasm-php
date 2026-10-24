<?php

namespace Oatmael\WasmPhp\Instruction;

use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Type\F64;
use Oatmael\WasmPhp\Util\Floats;

class F64Nearest implements InstructionInterface {
    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self();
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        $value = array_pop($stack);
        $stack[] = new F64(Floats::nearest($value->value));
    }
}
