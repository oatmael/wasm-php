<?php

namespace Oatmael\WasmPhp\Instruction\Extension;

use Exception;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Instruction\InstructionInterface;
use Oatmael\WasmPhp\Util\WasmReader;

class MemoryInit implements InstructionInterface
{
    public function __construct(
        public readonly int $data_idx,
        public readonly int $memory_idx,
    ) {}

    public static function fromInput(string $input, int &$offset): InstructionInterface
    {
        $data_idx = WasmReader::readLEB128Uint32($input, $offset);
        $memory_idx = WasmReader::readLEB128Uint32($input, $offset);
        return new self($data_idx, $memory_idx);
    }

    public function execute(array &$stack, array &$call_stack, Store $store)
    {
        throw new Exception('Not implemented: memory.init opcode');
    }
}
