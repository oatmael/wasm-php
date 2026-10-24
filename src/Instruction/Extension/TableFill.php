<?php

namespace Oatmael\WasmPhp\Instruction\Extension;

use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Instruction\InstructionInterface;
use Oatmael\WasmPhp\Util\WasmReader;

class TableFill implements InstructionInterface
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
        $count = $table->operand(array_pop($stack));
        $value = array_pop($stack);
        $index = $table->operand(array_pop($stack));

        $table->checkBounds($index, $count);
        for ($i = 0; $i < $count; $i++) {
            $table->elements[$index + $i] = $value;
        }
    }
}
