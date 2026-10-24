<?php

namespace Oatmael\WasmPhp\Instruction\Gc;

use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Instruction\InstructionInterface;
use Oatmael\WasmPhp\Type\StructRef;
use Oatmael\WasmPhp\Util\WasmReader;

class StructNewDefault implements InstructionInterface {
    public function __construct(
        public readonly int $type_idx,
    ) {}

    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self(WasmReader::readLEB128Uint32($input, $offset));
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        $fields = array_map(static fn ($field) => $field->defaultValue(), $store->types[$this->type_idx]->fields);
        $stack[] = new StructRef($store->canonical[$this->type_idx], $fields);
    }
}
