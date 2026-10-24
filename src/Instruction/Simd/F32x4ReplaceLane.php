<?php

namespace Oatmael\WasmPhp\Instruction\Simd;

use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Instruction\InstructionInterface;
use Oatmael\WasmPhp\Type\V128;
use Oatmael\WasmPhp\Util\Shape;
use Oatmael\WasmPhp\Util\WasmReader;

class F32x4ReplaceLane implements InstructionInterface {
    public function __construct(
        public readonly int $lane,
    ) {}

    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self(WasmReader::readUint8($input, $offset));
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        $value = array_pop($stack)->getBits();
        $lanes = array_pop($stack)->lanes(Shape::I32x4);
        $lanes[$this->lane] = $value;
        $stack[] = V128::fromLanes(Shape::I32x4, $lanes);
    }
}
