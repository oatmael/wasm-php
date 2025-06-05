<?php

namespace Oatmael\WasmPhp\Execution;

use Oatmael\WasmPhp\Type\Func;

class ControlStackEntry
{
    public function __construct(
        public int $break_target,
        public Func $return_type,
    )
    {
    }
}