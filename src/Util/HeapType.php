<?php

namespace Oatmael\WasmPhp\Util;

// Abstract heap types. Concrete heap types are type indices.
// https://webassembly.github.io/spec/core/binary/types.html#heap-types
enum HeapType: int {
    case NOEXN    = 0x74;
    case NOFUNC   = 0x73;
    case NOEXTERN = 0x72;
    case NONE     = 0x71;
    case FUNC     = 0x70;
    case EXTERN   = 0x6F;
    case ANY      = 0x6E;
    case EQ       = 0x6D;
    case I31      = 0x6C;
    case STRUCT   = 0x6B;
    case ARRAY    = 0x6A;
    case EXN      = 0x69;
}
