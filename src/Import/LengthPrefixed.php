<?php

namespace Oatmael\WasmPhp\Import;

use Attribute;
use Oatmael\WasmPhp\Type\Memory;

// A pointer to a little-endian u32 length in bytes, immediately followed by the string
#[Attribute(Attribute::TARGET_PARAMETER)]
class LengthPrefixed implements StringDecoder
{
    public function arity(): int
    {
        return 1;
    }

    public function read(Memory $memory, array $args, Encoding $encoding): string
    {
        [$pointer] = $args;
        $length = unpack('V', $memory->read($pointer, 4))[1];

        return $memory->read($pointer + 4, $length);
    }
}
