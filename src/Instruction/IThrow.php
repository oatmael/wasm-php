<?php

namespace Oatmael\WasmPhp\Instruction;

use Oatmael\WasmPhp\Exception\UncaughtException;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Type\ExnRef;
use Oatmael\WasmPhp\Util\WasmReader;

class IThrow implements InstructionInterface {
    public function __construct(
        public readonly int $tag_idx,
    ) {}

    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self(WasmReader::readLEB128Uint32($input, $offset));
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        $tag = $store->tags[$this->tag_idx];
        $values = array_splice($stack, count($stack) - count($tag->type->params));

        throw new UncaughtException(new ExnRef($tag, $values));
    }
}
