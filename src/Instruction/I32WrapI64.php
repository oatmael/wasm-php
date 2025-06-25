<?php

namespace Oatmael\WasmPhp\Instruction;

use Exception;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Type\I32;
use Oatmael\WasmPhp\Type\I64;

#[Opcode(StandardOpcode::i32_wrap_i64)]
class I32WrapI64 implements InstructionInterface {
    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self();
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        $value = array_pop($stack);
        if (!$value instanceof I64) {
            throw new Exception('Invalid stack params for i32.wrap_i64 opcode');
        }

        array_push($stack, new I32($value->getValue() & 0xFFFFFFFF));
    }
}