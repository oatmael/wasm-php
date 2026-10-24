<?php

namespace Oatmael\WasmPhp\Instruction;

use Exception;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Type\I64;

class I64Add implements InstructionInterface
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
            throw new Exception('Invalid stack params for i64.add opcode');
        }

        array_push($stack, new I64(I64::add($left->value, $right->value)));
    }
}
