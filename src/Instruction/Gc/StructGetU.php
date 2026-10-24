<?php

namespace Oatmael\WasmPhp\Instruction\Gc;

use Oatmael\WasmPhp\Exception\TrapException;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Instruction\InstructionInterface;
use Oatmael\WasmPhp\Type\StructRef;
use Oatmael\WasmPhp\Util\WasmReader;

// Zero extends packed fields
class StructGetU implements InstructionInterface {
    public function __construct(
        public readonly int $type_idx,
        public readonly int $field_idx,
    ) {}

    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self(WasmReader::readLEB128Uint32($input, $offset), WasmReader::readLEB128Uint32($input, $offset));
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        $struct = array_pop($stack);
        if (!($struct instanceof StructRef)) {
            throw new TrapException('null structure reference');
        }

        $stack[] = $store->types[$this->type_idx]->fields[$this->field_idx]->unpack($struct->fields[$this->field_idx]);
    }
}
