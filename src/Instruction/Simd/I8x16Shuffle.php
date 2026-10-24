<?php

namespace Oatmael\WasmPhp\Instruction\Simd;

use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Instruction\InstructionInterface;
use Oatmael\WasmPhp\Type\V128;
use Oatmael\WasmPhp\Util\WasmReader;

class I8x16Shuffle implements InstructionInterface {
    public function __construct(
        // The index of each result lane in the concatenation of both operands' bytes
        public readonly string $lanes,
    ) {}

    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self(WasmReader::readBytes($input, $offset, 16));
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        $bytes = array_pop($stack)->bytes;
        $bytes = array_pop($stack)->bytes . $bytes;

        $result = '';
        for ($i = 0; $i < 16; $i++) {
            $result .= $bytes[ord($this->lanes[$i]) & 0x1F];
        }
        $stack[] = new V128($result);
    }
}
