<?php

namespace Oatmael\WasmPhp\Instruction\Gc;

use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Instruction\InstructionInterface;
use Oatmael\WasmPhp\Type\ArrayRef;
use Oatmael\WasmPhp\Type\HostRef;
use Oatmael\WasmPhp\Type\I31Ref;
use Oatmael\WasmPhp\Type\NullRef;
use Oatmael\WasmPhp\Type\StructRef;

// Converts an externref to an anyref: internal references are unwrapped, and host values become HostRefs
class AnyConvertExtern implements InstructionInterface {
    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self();
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        $value = array_pop($stack);
        $stack[] = match (true) {
            $value instanceof NullRef => $value,
            $value->value instanceof I31Ref, $value->value instanceof StructRef, $value->value instanceof ArrayRef => $value->value,
            default => new HostRef($value->value),
        };
    }
}
