<?php

namespace Oatmael\WasmPhp\Instruction\Extension;

use Exception;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Instruction\InstructionInterface;
use Oatmael\WasmPhp\Instruction\ExtensionOpcode;
use Oatmael\WasmPhp\Instruction\Opcode;
use Oatmael\WasmPhp\Util\WasmReader;

#[Opcode(ExtensionOpcode::memory_copy)]
class MemoryCopy implements InstructionInterface
{
    public function __construct(
        public readonly int $src_offset,
        public readonly int $dst_offset,
        public readonly int $size,
    ) {}

    public static function fromInput(string $input, int &$offset): InstructionInterface
    {
        $src_offset = WasmReader::readLEB128Uint32($input, $offset);
        $dst_offset = WasmReader::readLEB128Uint32($input, $offset);
        $size = WasmReader::readLEB128Uint32($input, $offset);
        return new self($src_offset, $dst_offset, $size);
    }

    public function execute(array &$stack, array &$call_stack, Store $store)
    {
        throw new Exception('Not implemented: memory.copy opcode');
    }
}
