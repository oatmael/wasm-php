<?php

namespace Oatmael\WasmPhp\Instruction;

use Exception;
use Oatmael\WasmPhp\Exception\BadIntegerCastException;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Type\F32;
use Oatmael\WasmPhp\Type\I64;

class I64TruncF32U implements InstructionInterface
{
    public static function fromInput(string $input, int &$offset): InstructionInterface
    {
        return new self();
    }

    public function execute(array &$stack, array &$call_stack, Store $store)
    {
        $target = array_pop($stack);
        if (!($target instanceof F32)) {
            throw new Exception('Invalid stack params for i64.trunc_f32_u opcode');
        }

        $value = $target->getValue();
        if (is_nan($value)) {
            throw new BadIntegerCastException("Can't cast NaN to i64");
        }
        if (is_infinite($value)) {
            throw new BadIntegerCastException("Can't cast Infinity to i64");
        }

        // Only values in (-1, 2^64) fit once truncated
        if ($value <= -1.0 || $value >= 18446744073709551616.0) {
            throw new BadIntegerCastException('Integer overflow casting ' . $value . ' to unsigned i64');
        }

        // Values of 2^63 and above don't fit in a PHP int, so shift them down and set the sign bit to wrap them
        array_push($stack, new I64($value >= 9223372036854775808.0 ? (int) ($value - 9223372036854775808.0) | PHP_INT_MIN : (int) $value));
    }
}
