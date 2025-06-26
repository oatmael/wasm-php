<?php

namespace Oatmael\WasmPhp\Instruction;

use Exception;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Type\I32;

#[Opcode(StandardOpcode::i32_extend16_s)]
class I32Extend16S implements InstructionInterface {
    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self();
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        $target = array_pop($stack);

        if (!$target instanceof I32) {
            throw new Exception('Invalid operand types for i32.extend16_s');
        }

        $value = $target->getValue() & 0xFFFF;

        if ($value & 0x8000) {
            $value = $value | ((-1 << 16) & 0xFFFFFFFF);
        }

        array_push($stack, new I32($value));
    }
}