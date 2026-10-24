<?php

namespace Oatmael\WasmPhp\Util;

enum Section: int {
    // https://webassembly.github.io/spec/core/binary/modules.html#sections
    case CUSTOM     = 0x00;
    case TYPE       = 0x01;
    case IMPORT     = 0x02;
    case FUNCTION   = 0x03;
    case TABLE      = 0x04;
    case MEMORY     = 0x05;
    case GLOBAL     = 0x06;
    case EXPORT     = 0x07;
    case START      = 0x08;
    case ELEMENT    = 0x09;
    case CODE       = 0x0a;
    case DATA       = 0x0b;
    case DATA_COUNT = 0x0c;
    case TAG        = 0x0d;
}
