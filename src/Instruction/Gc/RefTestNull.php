<?php

namespace Oatmael\WasmPhp\Instruction\Gc;

use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Instruction\InstructionInterface;
use Oatmael\WasmPhp\Type\I32;
use Oatmael\WasmPhp\Type\RefType;
use Oatmael\WasmPhp\Util\HeapType;
use Oatmael\WasmPhp\Util\WasmReader;

class RefTestNull implements InstructionInterface {
    public function __construct(
        public readonly HeapType|int $heap_type,
    ) {}

    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self(WasmReader::readHeapType($input, $offset));
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        $stack[] = new I32($store->refMatches(array_pop($stack), new RefType(true, $this->heap_type)) ? 1 : 0);
    }
}
