<?php

namespace Oatmael\WasmPhp\Instruction\Gc;

use Oatmael\WasmPhp\Exception\TrapException;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Instruction\InstructionInterface;
use Oatmael\WasmPhp\Type\ArrayRef;
use Oatmael\WasmPhp\Util\WasmReader;

class ArrayCopy implements InstructionInterface {
    public function __construct(
        public readonly int $dst_type_idx,
        public readonly int $src_type_idx,
    ) {}

    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self(WasmReader::readLEB128Uint32($input, $offset), WasmReader::readLEB128Uint32($input, $offset));
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        $length = array_pop($stack)->value & 0xFFFFFFFF;
        $source_index = array_pop($stack)->value & 0xFFFFFFFF;
        $source = array_pop($stack);
        if (!($source instanceof ArrayRef)) {
            throw new TrapException('null array reference');
        }
        $index = array_pop($stack)->value & 0xFFFFFFFF;
        $array = array_pop($stack);
        if (!($array instanceof ArrayRef)) {
            throw new TrapException('null array reference');
        }
        if ($index > count($array->elements) - $length) {
            throw new TrapException('out of bounds array access');
        }
        if ($source_index > count($source->elements) - $length) {
            throw new TrapException('out of bounds array access');
        }

        // Slicing first copies the source, so overlapping ranges are handled
        array_splice($array->elements, $index, $length, array_slice($source->elements, $source_index, $length));
    }
}
