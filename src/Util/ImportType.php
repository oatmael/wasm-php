<?php

namespace Oatmael\WasmPhp\Util;

enum ImportType: int {
    case FUNCTION = 0x00;
    case TABLE    = 0x01;
    case MEMORY   = 0x02;
    case GLOBAL   = 0x03;
}
