<?php

namespace Oatmael\WasmPhp\Util;

enum GlobalMutability: int {
    case Immutable = 0x00;
    case Mutable   = 0x01;
}
