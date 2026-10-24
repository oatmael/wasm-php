<?php

namespace Oatmael\WasmPhp\Instruction\Gc;

use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Instruction\InstructionInterface;
use Oatmael\WasmPhp\Type\StructRef;
use Oatmael\WasmPhp\Util\WasmReader;

class StructNew implements InstructionInterface {
    public function __construct(
        public readonly int $type_idx,
    ) {}

    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self(WasmReader::readLEB128Uint32($input, $offset));
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        $type = $store->types[$this->type_idx];
        $values = array_splice($stack, count($stack) - count($type->fields));
        $stack[] = new StructRef($store->canonical[$this->type_idx], array_map(static fn ($field, $value) => $field->pack($value), $type->fields, $values));
    }
}
