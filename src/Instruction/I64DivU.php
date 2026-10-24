<?php

namespace Oatmael\WasmPhp\Instruction;

use Oatmael\WasmPhp\Exception\TrapException;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Type\I64;

class I64DivU implements InstructionInterface {
    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self();
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        $right = array_pop($stack);
        $left = array_pop($stack);
        if ($right->value === 0) {
            throw new TrapException('integer divide by zero');
        }

        $result = $left->toBigInteger(unsigned: true)->quotient($right->toBigInteger(unsigned: true));
        $stack[] = I64::fromBigInteger($result);
    }
}
