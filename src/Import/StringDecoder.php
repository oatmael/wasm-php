<?php

namespace Oatmael\WasmPhp\Import;

use Oatmael\WasmPhp\Type\Memory;

// How a string argument is laid out in memory. Implement this as a parameter attribute to support other layouts.
interface StringDecoder
{
    // Number of i32 arguments the string consumes from the wasm call
    public function arity(): int;

    // Read the string's raw bytes from memory, given its (unsigned) i32 arguments
    public function read(Memory $memory, array $args, Encoding $encoding): string;
}
