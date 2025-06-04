<?php

namespace Oatmael\WasmPhp\Instruction;

use Exception;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Util\WasmReader;

#[Opcode(StandardOpcode::memory_grow)]
class MemoryGrow implements InstructionInterface {
    public function __construct(
        public readonly int $reserved
    ) {}

    public static function fromInput(string $input, int &$offset): InstructionInterface {
        $reserved = WasmReader::readLEB128Uint32($input, $offset);
        return new self($reserved);
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        throw new Exception('Not implemented: memory.grow opcode');
    }
}