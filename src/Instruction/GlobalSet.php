<?php

namespace Oatmael\WasmPhp\Instruction;

use Exception;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Type\GlobalImmutable;
use Oatmael\WasmPhp\Util\WasmReader;

#[Opcode(StandardOpcode::global_set)]
class GlobalSet implements InstructionInterface {
    public function __construct(public readonly int $global_idx) {}

    public static function fromInput(string $input, int &$offset): InstructionInterface { 
        $global_idx = WasmReader::readLEB128Uint32($input, $offset);
        return new self($global_idx);
    }

    public function execute(array &$stack, array &$call_stack, Store $store) { 
        $value = array_pop($stack);
        if (!$value) {
            throw new Exception('No value on stack for global.set');
        }
        
        $global = $store->globals[$this->global_idx];
        if ($global instanceof GlobalImmutable) {
            throw new Exception("Can't set immutable global " . $this->global_idx);
        }

        $global->value = $value;
    }

}