<?php

namespace Oatmael\WasmPhp\Instruction\Gc;

use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Instruction\InstructionInterface;
use Oatmael\WasmPhp\Type\RefType;
use Oatmael\WasmPhp\Util\HeapType;
use Oatmael\WasmPhp\Util\WasmReader;

// Branches with the reference if it doesn't match the target type
class BrOnCastFail implements InstructionInterface {
    public function __construct(
        public readonly int $flags,
        public readonly int $depth,
        public readonly HeapType|int $source_type,
        public readonly HeapType|int $target_type,
    ) {}

    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self(WasmReader::readUint8($input, $offset), WasmReader::readLEB128Uint32($input, $offset), WasmReader::readHeapType($input, $offset), WasmReader::readHeapType($input, $offset));
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        // Bit 1 of the flags makes the target type nullable (bit 0 is the source type's, which is only used for validation)
        if (!$store->refMatches(end($stack), new RefType((bool) ($this->flags & 0x02), $this->target_type))) {
            $store->branch($stack, $call_stack, $this->depth);
        }
    }
}
