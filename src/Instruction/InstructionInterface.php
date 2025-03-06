<?php

namespace Oatmael\WasmPhp\Instruction;

interface InstructionInterface {
    public function execute();
    public static function fromInput(string $input, int &$offset): self;
}