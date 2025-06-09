<?php

namespace Oatmael\WasmPhp\Instruction;

use Exception;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Type\I32;

#[Opcode(StandardOpcode::i32_le_u)]
class I32LeU implements InstructionInterface {
    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self();
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        $left = array_pop($stack);
        $right = array_pop($stack);
        if (!($left instanceof I32) || !($right instanceof I32)) {
            throw new Exception('Invalid stack params for i32.ge_s opcode');
        }

        $left = $left->toUnsigned();
        $right = $right->toUnsigned();

        array_push($stack, new I32(($left->value <= $right->value) ? 1 : 0));
    }
}