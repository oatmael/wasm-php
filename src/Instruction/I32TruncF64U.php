<?php

namespace Oatmael\WasmPhp\Instruction;

use Exception;
use Oatmael\WasmPhp\Exception\BadIntegerCastException;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Type\F64;
use Oatmael\WasmPhp\Type\I32;

#[Opcode(StandardOpcode::i32_trunc_f64_u)]
class I32TruncF64U implements InstructionInterface {
    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self();
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        $target = array_pop($stack);
        if (!$target instanceof F64) {
            throw new Exception('Invalid stack params for i32.trunc_f64_u opcode');
        }

        if (is_nan($target->getValue())) {
            throw new BadIntegerCastException("Can't cast NaN to i32");
        }
        if (is_infinite($target->getValue())) {
            throw new BadIntegerCastException("Can't cast Infinity to i32");
        }
        if ($target->getValue() < 0) {
            throw new BadIntegerCastException("Can't cast negative value to unsigned i32");
        }

        array_push($stack, new I32((int)$target->getValue())->toUnsigned());
    }
}