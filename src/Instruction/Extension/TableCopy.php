<?php

namespace Oatmael\WasmPhp\Instruction\Extension;

use Exception;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Instruction\InstructionInterface;
use Oatmael\WasmPhp\Util\WasmReader;

class TableCopy implements InstructionInterface
{
    public function __construct(
        public readonly int $dst_table_idx,
        public readonly int $src_table_idx,
    ) {}

    public static function fromInput(string $input, int &$offset): InstructionInterface
    {
        $dst_table_idx = WasmReader::readLEB128Uint32($input, $offset);
        $src_table_idx = WasmReader::readLEB128Uint32($input, $offset);
        return new self($dst_table_idx, $src_table_idx);
    }

    public function execute(array &$stack, array &$call_stack, Store $store)
    {
        throw new Exception('Not implemented: table.copy opcode');
    }
}
