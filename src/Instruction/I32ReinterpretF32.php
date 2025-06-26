<?php

namespace Oatmael\WasmPhp\Instruction;

use Exception;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Type\F32;
use Oatmael\WasmPhp\Type\I32;

#[Opcode(StandardOpcode::i32_reinterpret_f32)]
class I32ReinterpretF32 implements InstructionInterface {
    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self();
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        $target = array_pop($stack);
        if (!($target instanceof F32)) {
            throw new Exception('Invalid stack params for i32.reinterpret_f32 opcode');
        }

        $reinterpret = pack('g', $target->getValue());
        $reinterpret = unpack('V', $reinterpret)[1];
        array_push($stack, new I32($reinterpret));
    }
}