<?php

namespace Oatmael\WasmPhp\Instruction\Extension;

use Exception;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Instruction\InstructionInterface;
use Oatmael\WasmPhp\Instruction\ExtensionOpcode;
use Oatmael\WasmPhp\Instruction\Opcode;
use Oatmael\WasmPhp\Util\WasmReader;

#[Opcode(ExtensionOpcode::elem_drop)]
class ElemDrop implements InstructionInterface
{
    public function __construct(
        public readonly int $elem_idx,
    ) {}

    public static function fromInput(string $input, int &$offset): InstructionInterface
    {
        $elem_idx = WasmReader::readLEB128Uint32($input, $offset);
        return new self($elem_idx);
    }

    public function execute(array &$stack, array &$call_stack, Store $store)
    {
        throw new Exception('Not implemented: elem.drop opcode');
    }
}
