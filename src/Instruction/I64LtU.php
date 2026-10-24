<?php

namespace Oatmael\WasmPhp\Instruction;

use Exception;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Type\I32;
use Oatmael\WasmPhp\Type\I64;

class I64LtU implements InstructionInterface
{
    public static function fromInput(string $input, int &$offset): InstructionInterface
    {
        return new self();
    }

    public function execute(array &$stack, array &$call_stack, Store $store)
    {
        $right = array_pop($stack);
        $left = array_pop($stack);
        if (!($left instanceof I64) || !($right instanceof I64)) {
            throw new Exception('Invalid stack params for i64.lt_u opcode');
        }

        $compare = $left->toBigInteger(unsigned: true)->compareTo($right->toBigInteger(unsigned: true));
        array_push($stack, new I32($compare < 0 ? 1 : 0));
    }
}
