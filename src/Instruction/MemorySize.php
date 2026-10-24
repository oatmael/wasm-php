<?php

namespace Oatmael\WasmPhp\Instruction;

use Exception;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Type\I32;
use Oatmael\WasmPhp\Type\Memory;
use Oatmael\WasmPhp\Util\WasmReader;

class MemorySize implements InstructionInterface {
    public function __construct(
        public readonly int $reserved
    ) {}

    public static function fromInput(string $input, int &$offset): InstructionInterface {
        $reserved = WasmReader::readLEB128Uint32($input, $offset);
        return new self($reserved);
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        if ($this->reserved !== 0) {
            throw new Exception('memory.size only supports single memory');
        }

        array_push($stack, new I32(intdiv(count($store->memory[$this->reserved]->data), Memory::PAGE_SIZE)));
    }
}