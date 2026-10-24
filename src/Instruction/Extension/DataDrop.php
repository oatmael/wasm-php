<?php

namespace Oatmael\WasmPhp\Instruction\Extension;

use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Instruction\InstructionInterface;
use Oatmael\WasmPhp\Util\WasmReader;

class DataDrop implements InstructionInterface
{
    public function __construct(
        public readonly int $data_idx,
    ) {}

    public static function fromInput(string $input, int &$offset): InstructionInterface
    {
        $data_idx = WasmReader::readLEB128Uint32($input, $offset);
        return new self($data_idx);
    }

    public function execute(array &$stack, array &$call_stack, Store $store)
    {
        $store->data[$this->data_idx] = '';
    }
}
