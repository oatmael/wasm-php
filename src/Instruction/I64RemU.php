<?php

namespace Oatmael\WasmPhp\Instruction;

use DivisionByZeroError;
use Exception;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Type\I64;

class I64RemU implements InstructionInterface
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
            throw new Exception('Invalid stack params for i64.rem_u opcode');
        }

        if ($right->value === 0) {
            throw new DivisionByZeroError('Integer divide by zero');
        }

        $result = $left->toBigInteger(unsigned: true)->remainder($right->toBigInteger(unsigned: true));
        array_push($stack, I64::fromBigInteger($result));
    }
}
