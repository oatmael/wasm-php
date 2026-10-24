<?php

namespace Oatmael\WasmPhp\Instruction;

use Exception;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Type\I64;

class I64Rotr implements InstructionInterface
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
            throw new Exception('Invalid stack params for i64.rotr opcode');
        }

        $count = $right->value & 63;
        if ($count === 0) {
            array_push($stack, $left);
            return;
        }

        $value = $left->value;
        array_push($stack, new I64((($value >> $count) & ~(-1 << (64 - $count))) | ($value << (64 - $count))));
    }
}
