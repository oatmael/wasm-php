<?php

namespace Oatmael\WasmPhp\Instruction\Gc;

use Oatmael\WasmPhp\Exception\TrapException;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Instruction\InstructionInterface;
use Oatmael\WasmPhp\Type\ArrayRef;
use Oatmael\WasmPhp\Type\FieldType;
use Oatmael\WasmPhp\Util\WasmReader;

// Reads the elements from a data segment, starting at a byte offset
class ArrayNewData implements InstructionInterface {
    public function __construct(
        public readonly int $type_idx,
        public readonly int $data_idx,
    ) {}

    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self(WasmReader::readLEB128Uint32($input, $offset), WasmReader::readLEB128Uint32($input, $offset));
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        $length = array_pop($stack)->value & 0xFFFFFFFF;
        $source = array_pop($stack)->value & 0xFFFFFFFF;

        $field = $store->types[$this->type_idx]->field;
        $stack[] = new ArrayRef($store->canonical[$this->type_idx], self::readData($store->data[$this->data_idx], $field, $source, $length));
    }

    // Decode $length elements from $data, starting $offset bytes in
    public static function readData(string $data, FieldType $field, int $offset, int $length): array
    {
        $size = $field->size();
        if ($offset > strlen($data) - $length * $size) {
            throw new TrapException('out of bounds memory access');
        }

        $values = [];
        for ($i = 0; $i < $length; $i++) {
            $values[] = $field->fromBytes(substr($data, $offset + $i * $size, $size));
        }

        return $values;
    }
}
