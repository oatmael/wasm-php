<?php

namespace Oatmael\WasmPhp\Instruction;

use Exception;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Type\I32;

#[Opcode(StandardOpcode::i32_popcnt)]
class I32Popcnt implements InstructionInterface {
    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self();
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        $test = array_pop($stack);
        if (!($test instanceof I32)) {
            throw new Exception('Invalid stack params for i32.popcnt opcode');
        }

        $n = $test->getValue();
        $n = ($n & 0x49249249) + (($n >> 1) & 0x49249249) + (($n >> 2) & 0x49249249);
        $n = ($n & 0xC30C30C3) + (($n >> 3) & 0x030C30C3);

        array_push($stack, new I32(0x3F & ( ($n * 0x41041041)  >> 30)));
    }
}