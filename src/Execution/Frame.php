<?php

namespace Oatmael\WasmPhp\Execution;

class Frame {
    public function __construct(
        public int $program_counter,
        public int $stack_pointer,
        public array $instructions,
        public int $arity,
        public array $locals,
    )
    {
    }
}
