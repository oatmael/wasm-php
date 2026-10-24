<?php

namespace Oatmael\WasmPhp\Instruction\Extension;

use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Instruction\InstructionInterface;
use Oatmael\WasmPhp\Type\I64;
use Oatmael\WasmPhp\Type\I32;
use Oatmael\WasmPhp\Util\WasmReader;

class TableSize implements InstructionInterface
{
    public function __construct(
        public readonly int $table_idx,
    ) {}

    public static function fromInput(string $input, int &$offset): InstructionInterface
    {
        $table_idx = WasmReader::readLEB128Uint32($input, $offset);
        return new self($table_idx);
    }

    public function execute(array &$stack, array &$call_stack, Store $store)
    {
        $table = $store->tables[$this->table_idx];
        $stack[] = $table->is64 ? new I64($table->size()) : new I32($table->size());
    }
}
