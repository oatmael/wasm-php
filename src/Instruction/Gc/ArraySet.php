<?php

namespace Oatmael\WasmPhp\Instruction\Gc;

use Oatmael\WasmPhp\Exception\TrapException;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Instruction\InstructionInterface;
use Oatmael\WasmPhp\Type\ArrayRef;
use Oatmael\WasmPhp\Util\WasmReader;

class ArraySet implements InstructionInterface {
    public function __construct(
        public readonly int $type_idx,
    ) {}

    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self(WasmReader::readLEB128Uint32($input, $offset));
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        $value = array_pop($stack);
        $index = array_pop($stack)->value & 0xFFFFFFFF;
        $array = array_pop($stack);
        if (!($array instanceof ArrayRef)) {
            throw new TrapException('null array reference');
        }
        if ($index >= count($array->elements)) {
            throw new TrapException('out of bounds array access');
        }

        $array->elements[$index] = $store->types[$this->type_idx]->field->pack($value);
    }
}
