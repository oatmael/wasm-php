<?php

namespace Oatmael\WasmPhp\Instruction\Extension;

use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Instruction\InstructionInterface;
use Oatmael\WasmPhp\Util\WasmReader;

class MemoryCopy implements InstructionInterface
{
    public function __construct(
        public readonly int $dst_memory_idx,
        public readonly int $src_memory_idx,
    ) {}

    public static function fromInput(string $input, int &$offset): InstructionInterface
    {
        $dst_memory_idx = WasmReader::readLEB128Uint32($input, $offset);
        $src_memory_idx = WasmReader::readLEB128Uint32($input, $offset);
        return new self($dst_memory_idx, $src_memory_idx);
    }

    public function execute(array &$stack, array &$call_stack, Store $store)
    {
        $dst = $store->memory[$this->dst_memory_idx];
        $src = $store->memory[$this->src_memory_idx];
        $count = array_pop($stack);
        $src_address = $src->operand(array_pop($stack));
        $dst_address = $dst->operand(array_pop($stack));

        // Both ranges are checked before anything is written
        $dst->write($dst_address, $src->read($src_address, $dst->operand($count)));
    }
}
