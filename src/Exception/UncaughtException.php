<?php

namespace Oatmael\WasmPhp\Exception;

use Oatmael\WasmPhp\Type\ExnRef;

// A wasm exception, thrown with throw or throw_ref. It unwinds the call stack until a try_table catches it, and
// escapes to PHP if nothing does.
class UncaughtException extends WasmException
{
    public function __construct(public readonly ExnRef $exception)
    {
        parent::__construct('Uncaught wasm exception');
    }
}
