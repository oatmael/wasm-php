<?php

namespace Oatmael\WasmPhp\Instruction;

use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Util\WasmReader;

class ReturnCall implements InstructionInterface {
    public function __construct(
        public readonly int $function_idx,
    ) {}

    public static function fromInput(string $input, int &$offset): InstructionInterface
    {
        return new self(WasmReader::readLEB128Uint32($input, $offset));
    }

    public function execute(array &$stack, array &$call_stack, Store $store)
    {
        $store->tailCall($stack, $call_stack, $store->funcs[$this->function_idx]);
    }
}
