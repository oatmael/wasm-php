<?php

namespace Oatmael\WasmPhp\Instruction\Simd;

use Exception;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Instruction\InstructionInterface;
use Oatmael\WasmPhp\Instruction\SimdOpcode;
use Oatmael\WasmPhp\Instruction\Opcode;
use Oatmael\WasmPhp\Util\WasmReader;

#[Opcode(SimdOpcode::v128_const)]
class V128Const implements InstructionInterface {
    public function __construct(public readonly string $bytes) {}

    public static function fromInput(string $input, int &$offset): InstructionInterface {
        $bytes = substr($input, $offset, 16);
        $offset += 16;
        return new self($bytes);
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        throw new Exception('Not implemented: v128.const opcode');
    }
}