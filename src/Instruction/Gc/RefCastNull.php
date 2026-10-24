<?php

namespace Oatmael\WasmPhp\Instruction\Gc;

use Oatmael\WasmPhp\Exception\TrapException;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Instruction\InstructionInterface;
use Oatmael\WasmPhp\Type\RefType;
use Oatmael\WasmPhp\Util\HeapType;
use Oatmael\WasmPhp\Util\WasmReader;

class RefCastNull implements InstructionInterface {
    public function __construct(
        public readonly HeapType|int $heap_type,
    ) {}

    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self(WasmReader::readHeapType($input, $offset));
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        if (!$store->refMatches(end($stack), new RefType(true, $this->heap_type))) {
            throw new TrapException('cast failure');
        }
    }
}
