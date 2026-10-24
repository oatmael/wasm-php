<?php

namespace Oatmael\WasmPhp\Instruction;

use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Type\I32;
use Oatmael\WasmPhp\Type\I64;
use Oatmael\WasmPhp\Util\WasmReader;

class MemoryGrow implements InstructionInterface {
    public function __construct(
        public readonly int $memory_idx
    ) {}

    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self(WasmReader::readLEB128Uint32($input, $offset));
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        $memory = $store->memory[$this->memory_idx];
        $previous = $memory->grow($memory->operand(array_pop($stack)));
        $stack[] = $memory->is64 ? new I64($previous) : new I32($previous);
    }
}
