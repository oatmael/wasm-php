<?php

namespace Oatmael\WasmPhp\Instruction;

use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Type\F64;
use Oatmael\WasmPhp\Util\Floats;

class F64ConvertI64S implements InstructionInterface {
    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self();
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        $stack[] = new F64(Floats::fromInt(array_pop($stack)->value, unsigned: false, to_f32: false));
    }
}
