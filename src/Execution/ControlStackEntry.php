<?php

namespace Oatmael\WasmPhp\Execution;

class ControlStackEntry
{
    public function __construct(
        // Program counter to jump to when branching to this label - execution resumes at the instruction after it
        public int $break_target,
        // Operand stack height when the block was entered (excluding its params)
        public int $stack_height,
        // Number of values a branch to this label carries (results for blocks, params for loops)
        public int $arity,
        // The catch clauses of a try_table, which handle exceptions thrown while this label is on the stack
        public array $catches = [],
    )
    {
    }
}
