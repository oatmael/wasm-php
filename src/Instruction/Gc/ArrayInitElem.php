<?php

namespace Oatmael\WasmPhp\Instruction\Gc;

use Oatmael\WasmPhp\Exception\TrapException;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Instruction\InstructionInterface;
use Oatmael\WasmPhp\Type\ArrayRef;
use Oatmael\WasmPhp\Util\WasmReader;

class ArrayInitElem implements InstructionInterface {
    public function __construct(
        public readonly int $type_idx,
        public readonly int $elem_idx,
    ) {}

    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self(WasmReader::readLEB128Uint32($input, $offset), WasmReader::readLEB128Uint32($input, $offset));
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        $length = array_pop($stack)->value & 0xFFFFFFFF;
        $source = array_pop($stack)->value & 0xFFFFFFFF;
        $index = array_pop($stack)->value & 0xFFFFFFFF;
        $array = array_pop($stack);
        if (!($array instanceof ArrayRef)) {
            throw new TrapException('null array reference');
        }
        if ($index > count($array->elements) - $length) {
            throw new TrapException('out of bounds array access');
        }

        $elements = $store->elements[$this->elem_idx];
        if ($source > count($elements) - $length) {
            throw new TrapException('out of bounds table access');
        }

        array_splice($array->elements, $index, $length, array_slice($elements, $source, $length));
    }
}
