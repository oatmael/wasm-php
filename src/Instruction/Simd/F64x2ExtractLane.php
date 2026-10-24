<?php

namespace Oatmael\WasmPhp\Instruction\Simd;

use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Instruction\InstructionInterface;
use Oatmael\WasmPhp\Type\F64;
use Oatmael\WasmPhp\Util\Shape;
use Oatmael\WasmPhp\Util\WasmReader;

class F64x2ExtractLane implements InstructionInterface {
    public function __construct(
        public readonly int $lane,
    ) {}

    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self(WasmReader::readUint8($input, $offset));
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        $stack[] = F64::fromBits(array_pop($stack)->lanes(Shape::I64x2)[$this->lane]);
    }
}
