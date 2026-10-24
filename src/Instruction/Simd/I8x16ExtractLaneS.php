<?php

namespace Oatmael\WasmPhp\Instruction\Simd;

use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Instruction\InstructionInterface;
use Oatmael\WasmPhp\Type\I32;
use Oatmael\WasmPhp\Util\Shape;
use Oatmael\WasmPhp\Util\WasmReader;

class I8x16ExtractLaneS implements InstructionInterface {
    public function __construct(
        public readonly int $lane,
    ) {}

    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self(WasmReader::readUint8($input, $offset));
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        $stack[] = new I32(array_pop($stack)->lanes(Shape::I8x16, signed: true)[$this->lane]);
    }
}
