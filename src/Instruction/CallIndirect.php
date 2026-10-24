<?php

namespace Oatmael\WasmPhp\Instruction;

use Oatmael\WasmPhp\Exception\TrapException;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Execution\TypeRegistry;
use Oatmael\WasmPhp\Type\FuncRef;
use Oatmael\WasmPhp\Util\WasmReader;

class CallIndirect implements InstructionInterface
{
    public function __construct(
        public readonly int $type_idx,
        public readonly int $table_idx,
    ) {}

    public static function fromInput(string $input, int &$offset): InstructionInterface
    {
        $type_idx = WasmReader::readLEB128Uint32($input, $offset);
        $table_idx = WasmReader::readLEB128Uint32($input, $offset);
        return new static($type_idx, $table_idx);
    }

    public function execute(array &$stack, array &$call_stack, Store $store)
    {
        $store->pushFrame($stack, $call_stack, $this->target($stack, $store));
    }

    // The function in the table at the index on top of the stack, which must match the expected type
    protected function target(array &$stack, Store $store): FuncRef
    {
        $table = $store->tables[$this->table_idx];
        $index = $table->operand(array_pop($stack));
        if ($index >= $table->size()) {
            throw new TrapException('undefined element');
        }

        $function = $table->elements[$index];
        if (!($function instanceof FuncRef)) {
            throw new TrapException('uninitialized element ' . $index);
        }

        if (!TypeRegistry::isSubtype($function->type_id, $store->canonical[$this->type_idx])) {
            throw new TrapException('indirect call type mismatch');
        }

        return $function;
    }
}
