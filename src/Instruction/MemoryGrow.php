<?php

namespace Oatmael\WasmPhp\Instruction;

use Exception;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Type\I32;
use Oatmael\WasmPhp\Util\WasmReader;

class MemoryGrow implements InstructionInterface {
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

        $delta = array_pop($stack);
        if (!$delta instanceof I32) {
            throw new Exception('Invalid stack operand for memory.grow');
        }

        array_push($stack, new I32($store->memory[$this->reserved]->grow($delta->toUnsigned()->value)));
    }
}