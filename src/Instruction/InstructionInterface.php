<?php

namespace Oatmael\WasmPhp\Instruction;

use Oatmael\WasmPhp\Execution\Store;

interface InstructionInterface {
    public function execute(array &$stack, array &$call_stack, Store $store);
    public static function fromInput(string $input, int &$offset): self;
}