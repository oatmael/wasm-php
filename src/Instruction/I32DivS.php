<?php

namespace Oatmael\WasmPhp\Instruction;

use Oatmael\WasmPhp\Exception\TrapException;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Type\I32;

class I32DivS implements InstructionInterface {
    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self();
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        $right = array_pop($stack)->getValue();
        $left = array_pop($stack)->getValue();
        if ($right === 0) {
            throw new TrapException('integer divide by zero');
        }
        if ($left === -0x80000000 && $right === -1) {
            throw new TrapException('integer overflow');
        }

        $stack[] = new I32(intdiv($left, $right));
    }
}
