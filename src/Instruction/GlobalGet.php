<?php

namespace Oatmael\WasmPhp\Instruction;

use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Util\WasmReader;

#[Opcode(StandardOpcode::global_get)]
class GlobalGet implements InstructionInterface {
    public function __construct(
        public readonly int $global_idx,
    ) {}

    public static function fromInput(string $input, int &$offset): InstructionInterface
    {
        $global_idx = WasmReader::readLEB128Uint32($input, $offset);
        return new self($global_idx);
    }

    public function execute(array &$stack, array &$call_stack, Store $store)
    {
        array_push($stack, $store->globals[$this->global_idx]->value);
    }

}