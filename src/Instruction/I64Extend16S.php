<?php

namespace Oatmael\WasmPhp\Instruction;

use Exception;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Type\I64;

class I64Extend16S implements InstructionInterface
{
    public static function fromInput(string $input, int &$offset): InstructionInterface
    {
        return new self();
    }

    public function execute(array &$stack, array &$call_stack, Store $store)
    {
        $target = array_pop($stack);
        if (!($target instanceof I64)) {
            throw new Exception('Invalid stack params for i64.extend16_s opcode');
        }

        $value = $target->getValue() & 0xFFFF;
        if ($value & 0x8000) {
            $value |= (-1 << 16);
        }

        array_push($stack, new I64($value));
    }
}
