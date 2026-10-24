<?php

namespace Oatmael\WasmPhp\Instruction;

// 0xfb prefixed opcodes (garbage collection), read as a LEB128 u32 after the prefix byte
enum GcOpcode: int {
    public const PREFIX = 0xfb;

    case struct_new          = 0x00;
    case struct_new_default  = 0x01;
    case struct_get          = 0x02;
    case struct_get_s        = 0x03;
    case struct_get_u        = 0x04;
    case struct_set          = 0x05;
    case array_new           = 0x06;
    case array_new_default   = 0x07;
    case array_new_fixed     = 0x08;
    case array_new_data      = 0x09;
    case array_new_elem      = 0x0a;
    case array_get           = 0x0b;
    case array_get_s         = 0x0c;
    case array_get_u         = 0x0d;
    case array_set           = 0x0e;
    case array_len           = 0x0f;
    case array_fill          = 0x10;
    case array_copy          = 0x11;
    case array_init_data     = 0x12;
    case array_init_elem     = 0x13;
    case ref_test            = 0x14;
    case ref_test_null       = 0x15;
    case ref_cast            = 0x16;
    case ref_cast_null       = 0x17;
    case br_on_cast          = 0x18;
    case br_on_cast_fail     = 0x19;
    case any_convert_extern  = 0x1a;
    case extern_convert_any  = 0x1b;
    case ref_i31             = 0x1c;
    case i31_get_s           = 0x1d;
    case i31_get_u           = 0x1e;
}
