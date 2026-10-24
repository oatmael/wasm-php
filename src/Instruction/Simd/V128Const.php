<?php

namespace Oatmael\WasmPhp\Instruction\Simd;

use Exception;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Instruction\InstructionInterface;
use Oatmael\WasmPhp\Util\WasmReader;

class V128Const implements InstructionInterface {
    public function __construct(public readonly string $bytes) {}

    public static function fromInput(string $input, int &$offset): InstructionInterface {
        $bytes = WasmReader::readBytes($input, $offset, 16);
        return new self($bytes);
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        throw new Exception('Not implemented: v128.const opcode');
    }
}