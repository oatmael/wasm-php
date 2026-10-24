<?php

namespace Oatmael\WasmPhp\Instruction;

// 0xfc prefixed opcodes, read as a LEB128 u32 after the prefix byte
enum ExtensionOpcode: int {
    public const PREFIX = 0xfc;

    case i32_trunc_sat_f32_s = 0x00;
    case i32_trunc_sat_f32_u = 0x01;
    case i32_trunc_sat_f64_s = 0x02;
    case i32_trunc_sat_f64_u = 0x03;
    case i64_trunc_sat_f32_s = 0x04;
    case i64_trunc_sat_f32_u = 0x05;
    case i64_trunc_sat_f64_s = 0x06;
    case i64_trunc_sat_f64_u = 0x07;
    case memory_init         = 0x08;
    case data_drop           = 0x09;
    case memory_copy         = 0x0a;
    case memory_fill         = 0x0b;
    case table_init          = 0x0c;
    case elem_drop           = 0x0d;
    case table_copy          = 0x0e;
    case table_grow          = 0x0f;
    case table_size          = 0x10;
    case table_fill          = 0x11;
}
