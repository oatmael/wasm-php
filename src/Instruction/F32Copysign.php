<?php

namespace Oatmael\WasmPhp\Instruction;

use Exception;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Type\F32;
use Oatmael\WasmPhp\Type\I32;

#[Opcode(StandardOpcode::f32_copysign)]
class F32Copysign implements InstructionInterface {
    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self();
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        $right = array_pop($stack);
        $left = array_pop($stack);

        if (!$left instanceof F32 || !$right instanceof F32) {
            throw new Exception('Invalid operand types for f32.copysign');
        }

        $left_bits = pack('g', $left->getValue());
        $left_bits = new I32(unpack('V', $left_bits)[1]);

        $right_bits = pack('g', $right->getValue());
        $right_bits = new I32(unpack('V', $right_bits)[1]);

        $result_bits = $right_bits->getValue() & 0x80000000 | $left_bits->getValue() & 0x7FFFFFFF;

        $result = pack('V', $result_bits);
        $result = unpack('g', $result)[1];

        array_push($stack, new F32($result));
    }
}