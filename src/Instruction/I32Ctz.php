<?php

namespace Oatmael\WasmPhp\Instruction;

use Exception;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Type\I32;

#[Opcode(StandardOpcode::i32_ctz)]
class I32Ctz implements InstructionInterface
{
    public static function fromInput(string $input, int &$offset): InstructionInterface
    {
        return new self();
    }

    public function execute(array &$stack, array &$call_stack, Store $store)
    {
        $test = array_pop($stack);
        if (!($test instanceof I32)) {
            throw new Exception('Invalid stack parameter for i32.ctz opcode');
        }

        $x = $test->value;
        if ($x === 0) {
            array_push($stack, new I32(32));
            return;
        }
        $n = 1;
        if (($x & 0x0000FFFF) === 0) {
            $n = $n + 16;
            $x = $x >> 16;
        }
        if (($x & 0x000000FF) === 0) {
            $n = $n + 8;
            $x = $x >> 8;
        }
        if (($x & 0x0000000F) === 0) {
            $n = $n + 4;
            $x = $x >> 4;
        }
        if (($x & 0x00000003) === 0) {
            $n = $n + 2;
            $x = $x >> 2;
        }
        
        array_push($stack, new I32($n - ($x & 1)));
    }
}
