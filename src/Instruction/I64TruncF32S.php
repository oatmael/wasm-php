<?php

namespace Oatmael\WasmPhp\Instruction;

use Exception;
use Oatmael\WasmPhp\Exception\BadIntegerCastException;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Type\F32;
use Oatmael\WasmPhp\Type\I64;

class I64TruncF32S implements InstructionInterface
{
    public static function fromInput(string $input, int &$offset): InstructionInterface
    {
        return new self();
    }

    public function execute(array &$stack, array &$call_stack, Store $store)
    {
        $target = array_pop($stack);
        if (!($target instanceof F32)) {
            throw new Exception('Invalid stack params for i64.trunc_f32_s opcode');
        }

        $value = $target->getValue();
        if (is_nan($value)) {
            throw new BadIntegerCastException("Can't cast NaN to i64");
        }
        if (is_infinite($value)) {
            throw new BadIntegerCastException("Can't cast Infinity to i64");
        }

        // Only values in [-2^63, 2^63) fit once truncated
        if ($value < -9223372036854775808.0 || $value >= 9223372036854775808.0) {
            throw new BadIntegerCastException('Integer overflow casting ' . $value . ' to i64');
        }

        array_push($stack, new I64((int) $value));
    }
}
