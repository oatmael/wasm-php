<?php

namespace Oatmael\WasmPhp\Instruction;

use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Type\NullRef;
use Oatmael\WasmPhp\Util\HeapType;
use Oatmael\WasmPhp\Util\WasmReader;

class RefNull implements InstructionInterface {
    public function __construct(public readonly HeapType|int $heap_type) {}

    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self(WasmReader::readHeapType($input, $offset));
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        $stack[] = new NullRef();
    }
}
