<?php

namespace Oatmael\WasmPhp\Instruction;

use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Type\NullRef;
use Oatmael\WasmPhp\Util\WasmReader;

// Branches if the reference is null, otherwise leaves it on the stack
class BrOnNull implements InstructionInterface {
    public function __construct(public readonly int $depth) {}

    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self(WasmReader::readLEB128Uint32($input, $offset));
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        if (end($stack) instanceof NullRef) {
            array_pop($stack);
            $store->branch($stack, $call_stack, $this->depth);
        }
    }
}
