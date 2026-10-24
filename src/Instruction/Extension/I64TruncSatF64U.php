<?php

namespace Oatmael\WasmPhp\Instruction\Extension;

use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Instruction\InstructionInterface;
use Oatmael\WasmPhp\Type\I64;
use Oatmael\WasmPhp\Util\Floats;

class I64TruncSatF64U implements InstructionInterface {
    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self();
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        $stack[] = new I64(Floats::toIntSaturated(array_pop($stack)->value, 64, signed: false));
    }
}
