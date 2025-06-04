<?php

namespace Oatmael\WasmPhp\Instruction\Extension;

use Exception;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Instruction\InstructionInterface;
use Oatmael\WasmPhp\Instruction\ExtensionOpcode;
use Oatmael\WasmPhp\Instruction\Opcode;
use Oatmael\WasmPhp\Util\WasmReader;

#[Opcode(ExtensionOpcode::memory_fill)]
class MemoryFill implements InstructionInterface
{
    public function __construct(
        public readonly int $offset,
        public readonly int $value,
        public readonly int $size,
    ) {}

    public static function fromInput(string $input, int &$offset): InstructionInterface
    {
        $offset = WasmReader::readLEB128Uint32($input, $offset);
        $value = WasmReader::readLEB128int32($input, $offset);
        $size = WasmReader::readLEB128Uint32($input, $offset);
        return new self($offset, $value, $size);
    }

    public function execute(array &$stack, array &$call_stack, Store $store)
    {
        throw new Exception('Not implemented: memory.fill opcode');
    }
}
