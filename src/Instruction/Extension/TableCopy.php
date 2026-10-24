<?php

namespace Oatmael\WasmPhp\Instruction\Extension;

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
        $dst = $store->tables[$this->dst_table_idx];
        $src = $store->tables[$this->src_table_idx];
        $count = array_pop($stack);
        $src_index = $src->operand(array_pop($stack));
        $dst_index = $dst->operand(array_pop($stack));

        // Both ranges are checked before anything is written
        $dst->write($dst_index, $src->read($src_index, $dst->operand($count)));
    }
}
