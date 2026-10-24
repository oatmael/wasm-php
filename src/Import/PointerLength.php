<?php

namespace Oatmael\WasmPhp\Import;

use Attribute;
use Oatmael\WasmPhp\Type\Memory;

// A pointer and a length in bytes - the default for string parameters
#[Attribute(Attribute::TARGET_PARAMETER)]
class PointerLength implements StringDecoder
{
    public function arity(): int
    {
        return 2;
    }

    public function read(Memory $memory, array $args, Encoding $encoding): string
    {
        [$pointer, $length] = $args;
        return $memory->read($pointer, $length);
    }
}
