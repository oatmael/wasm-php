<?php

namespace Oatmael\WasmPhp\Instruction;

use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Type\I32;
use Oatmael\WasmPhp\Util\Floats;

class I32TruncF32U implements InstructionInterface {
    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self();
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        $stack[] = new I32(Floats::toInt(array_pop($stack)->value, 32, signed: false));
    }
}
