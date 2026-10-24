<?php

namespace Oatmael\WasmPhp\Instruction;

use Oatmael\WasmPhp\Exception\TrapException;
use Oatmael\WasmPhp\Exception\UncaughtException;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Type\ExnRef;

class ThrowRef implements InstructionInterface {
    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self();
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        $exception = array_pop($stack);
        if (!($exception instanceof ExnRef)) {
            throw new TrapException('null exception reference');
        }

        throw new UncaughtException($exception);
    }
}
