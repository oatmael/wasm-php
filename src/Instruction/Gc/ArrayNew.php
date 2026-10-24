<?php

namespace Oatmael\WasmPhp\Instruction\Gc;

use Oatmael\WasmPhp\Exception\TrapException;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Instruction\InstructionInterface;
use Oatmael\WasmPhp\Type\ArrayRef;
use Oatmael\WasmPhp\Util\WasmReader;

class ArrayNew implements InstructionInterface {
    public function __construct(
        public readonly int $type_idx,
    ) {}

    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self(WasmReader::readLEB128Uint32($input, $offset));
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        $length = array_pop($stack)->value & 0xFFFFFFFF;
        $value = array_pop($stack);
        self::checkLength($length);

        $field = $store->types[$this->type_idx]->field;
        $stack[] = new ArrayRef($store->canonical[$this->type_idx], $length ? array_fill(0, $length, $field->pack($value)) : []);
    }

    // Arrays beyond this many elements are refused, as each element takes a PHP array slot
    public const MAX_LENGTH = 10_000_000;

    public static function checkLength(int $length): void
    {
        if ($length > self::MAX_LENGTH) {
            throw new TrapException("out of memory: can't allocate an array of $length elements");
        }
    }
}
