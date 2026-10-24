<?php

namespace Oatmael\WasmPhp\Import;

use Attribute;
use Exception;

// The encoding a string argument is stored in, converted to UTF-8 before being passed to PHP
#[Attribute(Attribute::TARGET_PARAMETER)]
class Encoding
{
    public function __construct(
        public readonly string $encoding = 'UTF-8',
    )
    {
        // Throws a ValueError for unknown encodings, so typos are caught when the import is registered
        mb_check_encoding('', $this->encoding);
    }

    // Size in bytes of one code unit, used to find null terminators
    public function unitWidth(): int
    {
        $encoding = strtoupper($this->encoding);
        return match (true) {
            str_starts_with($encoding, 'UTF-16'), str_starts_with($encoding, 'UCS-2') => 2,
            str_starts_with($encoding, 'UTF-32'), str_starts_with($encoding, 'UCS-4') => 4,
            default => 1,
        };
    }

    public function decode(string $bytes): string
    {
        if (!mb_check_encoding($bytes, $this->encoding)) {
            throw new Exception("Invalid {$this->encoding} string");
        }

        return mb_convert_encoding($bytes, 'UTF-8', $this->encoding);
    }
}
