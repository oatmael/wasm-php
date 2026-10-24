<?php

namespace Oatmael\WasmPhp\Instruction\Simd;

use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Instruction\InstructionInterface;
use Oatmael\WasmPhp\Type\V128;
use Oatmael\WasmPhp\Util\WasmReader;

class V128Const implements InstructionInterface {
    public function __construct(
        public readonly string $bytes,
    ) {}

    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self(WasmReader::readBytes($input, $offset, 16));
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        $stack[] = new V128($this->bytes);
    }
}
