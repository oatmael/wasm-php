<?php

namespace Oatmael\WasmPhp\Instruction\Gc;

use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Instruction\InstructionInterface;
use Oatmael\WasmPhp\Type\ExternRef;
use Oatmael\WasmPhp\Type\HostRef;
use Oatmael\WasmPhp\Type\NullRef;

// Converts an anyref to an externref, wrapping internal references so any.convert_extern can unwrap them
class ExternConvertAny implements InstructionInterface {
    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self();
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        $value = array_pop($stack);
        $stack[] = match (true) {
            $value instanceof NullRef => $value,
            $value instanceof HostRef => new ExternRef($value->value),
            default                   => new ExternRef($value),
        };
    }
}
