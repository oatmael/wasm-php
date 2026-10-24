<?php

namespace Oatmael\WasmPhp\Instruction\Gc;

use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Instruction\InstructionInterface;
use Oatmael\WasmPhp\Type\ArrayRef;
use Oatmael\WasmPhp\Util\WasmReader;

class ArrayNewFixed implements InstructionInterface {
    public function __construct(
        public readonly int $type_idx,
        public readonly int $length,
    ) {}

    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self(WasmReader::readLEB128Uint32($input, $offset), WasmReader::readLEB128Uint32($input, $offset));
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        $field = $store->types[$this->type_idx]->field;
        $values = $this->length ? array_splice($stack, -$this->length) : [];
        $stack[] = new ArrayRef($store->canonical[$this->type_idx], array_map($field->pack(...), $values));
    }
}
