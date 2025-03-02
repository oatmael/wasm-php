<?php

namespace Oatmael\WasmPhp\Type;

use Oatmael\WasmPhp\Util\Opcode;

class Instruction {
    public function __construct(
        public readonly Opcode $opcode,
        public readonly array $args
    )
    {
    }
}