<?php

namespace Oatmael\WasmPhp\Instruction;

use Exception;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Type\I32;
use Oatmael\WasmPhp\Type\I64;
use Oatmael\WasmPhp\Util\WasmReader;

#[Opcode(StandardOpcode::memory_grow)]
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
        if (!$delta instanceof I32 && !$delta instanceof I64) {
            throw new Exception('Invalid stack operand for memory.grow');
        }

        $ptr_class = PHP_INT_SIZE === 8 ? I64::class : I32::class;
        array_push($stack, new $ptr_class($store->memory[$this->reserved]->grow($delta->toUnsigned()->value)));
    }
}