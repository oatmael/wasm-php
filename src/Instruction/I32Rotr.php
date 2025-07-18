<?php

namespace Oatmael\WasmPhp\Instruction;

use Exception;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Type\I32;

#[Opcode(StandardOpcode::i32_rotr)]
class I32Rotr implements InstructionInterface {
public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self();
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        $target = array_pop($stack);
        $count = array_pop($stack);

        if (!$target instanceof I32 || !$count instanceof I32) {
            throw new Exception('Invalid operand types for i32.rotr');
        }

        $value = $target->getValue() & 0xFFFFFFFF;
        $count = $count->getValue() & 0x1F;

        if ($count === 0) {
            array_push($stack, $target);
            return;
        }

        $value = ($value >> $count) | ($value << (32 - $count));

        array_push($stack, new I32($value));
    }
}