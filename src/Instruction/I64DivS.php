<?php

namespace Oatmael\WasmPhp\Instruction;

use Oatmael\WasmPhp\Exception\TrapException;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Type\I64;

class I64DivS implements InstructionInterface {
    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self();
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        $right = array_pop($stack)->value;
        $left = array_pop($stack)->value;
        if ($right === 0) {
            throw new TrapException('integer divide by zero');
        }
        if ($left === PHP_INT_MIN && $right === -1) {
            throw new TrapException('integer overflow');
        }

        $stack[] = new I64(intdiv($left, $right));
    }
}
