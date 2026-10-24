<?php

namespace Oatmael\WasmPhp\Instruction;

use Oatmael\WasmPhp\Exception\TrapException;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Type\I32;

class I32DivU implements InstructionInterface {
    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self();
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        $right = array_pop($stack)->value & 0xFFFFFFFF;
        $left = array_pop($stack)->value & 0xFFFFFFFF;
        if ($right === 0) {
            throw new TrapException('integer divide by zero');
        }

        $stack[] = new I32(intdiv($left, $right));
    }
}
