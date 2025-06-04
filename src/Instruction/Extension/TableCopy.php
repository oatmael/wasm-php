<?php

namespace Oatmael\WasmPhp\Instruction\Extension;

use Exception;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Instruction\InstructionInterface;
use Oatmael\WasmPhp\Instruction\ExtensionOpcode;
use Oatmael\WasmPhp\Instruction\Opcode;
use Oatmael\WasmPhp\Util\WasmReader;

#[Opcode(ExtensionOpcode::table_copy)]
class TableCopy implements InstructionInterface
{
    public function __construct(
        public readonly int $src_table_idx,
        public readonly int $dst_table_idx,
        public readonly int $offset,
    ) {}

    public static function fromInput(string $input, int &$offset): InstructionInterface
    {
        $src_table_idx = WasmReader::readLEB128Uint32($input, $offset);
        $dst_table_idx = WasmReader::readLEB128Uint32($input, $offset);
        $offset = WasmReader::readLEB128Uint32($input, $offset);
        return new self($src_table_idx, $dst_table_idx, $offset);
    }

    public function execute(array &$stack, array &$call_stack, Store $store)
    {
        throw new Exception('Not implemented: table.copy opcode');
    }
}
