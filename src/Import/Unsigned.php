<?php

namespace Oatmael\WasmPhp\Import;

use Attribute;

// Receive an i32 argument as an unsigned int (0 to 2^32 - 1) instead of signed
#[Attribute(Attribute::TARGET_PARAMETER)]
class Unsigned
{
}
