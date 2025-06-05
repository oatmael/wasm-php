<?php

namespace Oatmael\WasmPhp\Instruction;

use Exception;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Util\ValueType;
use Oatmael\WasmPhp\Util\WasmReader;

#[Opcode(StandardOpcode::if)]
class IIf implements InstructionInterface {
    public function __construct(
        public readonly ValueType $block_type,
    ) {}

    public static function fromInput(string $input, int &$offset): InstructionInterface {
        $block_type = ValueType::from(WasmReader::readUint8($input, $offset));
        return new self($block_type);
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        throw new Exception('Not implemented: if opcode');
    }
}