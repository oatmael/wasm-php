<?php

namespace Oatmael\WasmPhp\Instruction;

use Oatmael\WasmPhp\Exception\TrapException;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Type\FuncRef;
use Oatmael\WasmPhp\Util\WasmReader;

class CallRef implements InstructionInterface {
    public function __construct(
        public readonly int $type_idx,
    ) {}

    public static function fromInput(string $input, int &$offset): InstructionInterface
    {
        return new static(WasmReader::readLEB128Uint32($input, $offset));
    }

    public function execute(array &$stack, array &$call_stack, Store $store)
    {
        $store->pushFrame($stack, $call_stack, self::target($stack));
    }

    protected static function target(array &$stack): FuncRef
    {
        $function = array_pop($stack);
        return $function instanceof FuncRef ? $function : throw new TrapException('null function reference');
    }
}
