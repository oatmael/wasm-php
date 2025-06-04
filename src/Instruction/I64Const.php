<?php

namespace Oatmael\WasmPhp\Instruction;

use Exception;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Util\WasmReader;

#[Opcode(StandardOpcode::i64_const)]
class I64Const implements InstructionInterface {
    public function __construct(
        public readonly int $value,
    ) {}

    public static function fromInput(string $input, int &$offset): InstructionInterface {
        $value = WasmReader::readLEB128int32($input, $offset);
        return new self($value);
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
      throw new Exception('Not implemented: i64.const opcode');
    }
}