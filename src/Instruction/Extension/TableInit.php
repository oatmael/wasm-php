<?php

namespace Oatmael\WasmPhp\Instruction\Extension;

use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Instruction\InstructionInterface;
use Oatmael\WasmPhp\Util\WasmReader;

class TableInit implements InstructionInterface
{
    public function __construct(
        public readonly int $elem_idx,
        public readonly int $table_idx,
    ) {}

    public static function fromInput(string $input, int &$offset): InstructionInterface
    {
        $elem_idx = WasmReader::readLEB128Uint32($input, $offset);
        $table_idx = WasmReader::readLEB128Uint32($input, $offset);
        return new self($elem_idx, $table_idx);
    }

    public function execute(array &$stack, array &$call_stack, Store $store)
    {
        $table = $store->tables[$this->table_idx];
        $count = array_pop($stack)->value & 0xFFFFFFFF;
        $src = array_pop($stack)->value & 0xFFFFFFFF;
        $store->initTable($table, $this->elem_idx, $table->operand(array_pop($stack)), $src, $count);
    }
}
