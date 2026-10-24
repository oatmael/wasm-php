<?php

namespace Oatmael\WasmPhp\Import;

use Attribute;
use Oatmael\WasmPhp\Type\Memory;

// A pointer to a string ending in a null code unit (C-style)
#[Attribute(Attribute::TARGET_PARAMETER)]
class NullTerminated implements StringDecoder
{
    public function arity(): int
    {
        return 1;
    }

    public function read(Memory $memory, array $args, Encoding $encoding): string
    {
        [$pointer] = $args;
        $width = $encoding->unitWidth();
        $terminator = str_repeat("\0", $width);

        $end = $pointer;
        while ($memory->read($end, $width) !== $terminator) {
            $end += $width;
        }

        return $memory->read($pointer, $end - $pointer);
    }
}
