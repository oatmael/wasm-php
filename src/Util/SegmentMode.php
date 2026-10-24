<?php

namespace Oatmael\WasmPhp\Util;

// https://webassembly.github.io/spec/core/syntax/modules.html#element-segments
enum SegmentMode {
    // Copied into a table or memory when the module is instantiated
    case ACTIVE;
    // Only used by memory.init, table.init and array instructions
    case PASSIVE;
    // Only declares the functions it references, so they can be used with ref.func
    case DECLARATIVE;
}
