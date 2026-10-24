<?php

namespace Oatmael\WasmPhp\Instruction;

// 0xfd prefixed opcodes, read as a LEB128 u32 after the prefix byte
enum SimdOpcode: int {
    public const PREFIX = 0xfd;

    case v128_load                      = 0x00;
    case v128_load8x8_s                 = 0x01;
    case v128_load8x8_u                 = 0x02;
    case v128_load16x4_s                = 0x03;
    case v128_load16x4_u                = 0x04;
    case v128_load32x2_s                = 0x05;
    case v128_load32x2_u                = 0x06;
    case v128_load8_splat               = 0x07;
    case v128_load16_splat              = 0x08;
    case v128_load32_splat              = 0x09;
    case v128_load64_splat              = 0x0a;
    case v128_store                     = 0x0b;
    case v128_const                     = 0x0c;
    case i8x16_shuffle                  = 0x0d;
    case i8x16_swizzle                  = 0x0e;
    case i8x16_splat                    = 0x0f;
    case i16x8_splat                    = 0x10;
    case i32x4_splat                    = 0x11;
    case i64x2_splat                    = 0x12;
    case f32x4_splat                    = 0x13;
    case f64x2_splat                    = 0x14;
    case i8x16_extract_lane_s           = 0x15;
    case i8x16_extract_lane_u           = 0x16;
    case i8x16_replace_lane             = 0x17;
    case i16x8_extract_lane_s           = 0x18;
    case i16x8_extract_lane_u           = 0x19;
    case i16x8_replace_lane             = 0x1a;
    case i32x4_extract_lane             = 0x1b;
    case i32x4_replace_lane             = 0x1c;
    case i64x2_extract_lane             = 0x1d;
    case i64x2_replace_lane             = 0x1e;
    case f32x4_extract_lane             = 0x1f;
    case f32x4_replace_lane             = 0x20;
    case f64x2_extract_lane             = 0x21;
    case f64x2_replace_lane             = 0x22;
    case i8x16_eq                       = 0x23;
    case i8x16_ne                       = 0x24;
    case i8x16_lt_s                     = 0x25;
    case i8x16_lt_u                     = 0x26;
    case i8x16_gt_s                     = 0x27;
    case i8x16_gt_u                     = 0x28;
    case i8x16_le_s                     = 0x29;
    case i8x16_le_u                     = 0x2a;
    case i8x16_ge_s                     = 0x2b;
    case i8x16_ge_u                     = 0x2c;
    case i16x8_eq                       = 0x2d;
    case i16x8_ne                       = 0x2e;
    case i16x8_lt_s                     = 0x2f;
    case i16x8_lt_u                     = 0x30;
    case i16x8_gt_s                     = 0x31;
    case i16x8_gt_u                     = 0x32;
    case i16x8_le_s                     = 0x33;
    case i16x8_le_u                     = 0x34;
    case i16x8_ge_s                     = 0x35;
    case i16x8_ge_u                     = 0x36;
    case i32x4_eq                       = 0x37;
    case i32x4_ne                       = 0x38;
    case i32x4_lt_s                     = 0x39;
    case i32x4_lt_u                     = 0x3a;
    case i32x4_gt_s                     = 0x3b;
    case i32x4_gt_u                     = 0x3c;
    case i32x4_le_s                     = 0x3d;
    case i32x4_le_u                     = 0x3e;
    case i32x4_ge_s                     = 0x3f;
    case i32x4_ge_u                     = 0x40;
    case f32x4_eq                       = 0x41;
    case f32x4_ne                       = 0x42;
    case f32x4_lt                       = 0x43;
    case f32x4_gt                       = 0x44;
    case f32x4_le                       = 0x45;
    case f32x4_ge                       = 0x46;
    case f64x2_eq                       = 0x47;
    case f64x2_ne                       = 0x48;
    case f64x2_lt                       = 0x49;
    case f64x2_gt                       = 0x4a;
    case f64x2_le                       = 0x4b;
    case f64x2_ge                       = 0x4c;
    case v128_not                       = 0x4d;
    case v128_and                       = 0x4e;
    case v128_andnot                    = 0x4f;
    case v128_or                        = 0x50;
    case v128_xor                       = 0x51;
    case v128_bitselect                 = 0x52;
    case v128_any_true                  = 0x53;
    case v128_load8_lane                = 0x54;
    case v128_load16_lane               = 0x55;
    case v128_load32_lane               = 0x56;
    case v128_load64_lane               = 0x57;
    case v128_store8_lane               = 0x58;
    case v128_store16_lane              = 0x59;
    case v128_store32_lane              = 0x5a;
    case v128_store64_lane              = 0x5b;
    case v128_load32_zero               = 0x5c;
    case v128_load64_zero               = 0x5d;
    case f32x4_demote_f64x2_zero        = 0x5e;
    case f64x2_promote_low_f32x4        = 0x5f;
    case i8x16_abs                      = 0x60;
    case i8x16_neg                      = 0x61;
    case i8x16_popcnt                   = 0x62;
    case i8x16_all_true                 = 0x63;
    case i8x16_bitmask                  = 0x64;
    case i8x16_narrow_i16x8_s           = 0x65;
    case i8x16_narrow_i16x8_u           = 0x66;
    case f32x4_ceil                     = 0x67;
    case f32x4_floor                    = 0x68;
    case f32x4_trunc                    = 0x69;
    case f32x4_nearest                  = 0x6a;
    case i8x16_shl                      = 0x6b;
    case i8x16_shr_s                    = 0x6c;
    case i8x16_shr_u                    = 0x6d;
    case i8x16_add                      = 0x6e;
    case i8x16_add_sat_s                = 0x6f;
    case i8x16_add_sat_u                = 0x70;
    case i8x16_sub                      = 0x71;
    case i8x16_sub_sat_s                = 0x72;
    case i8x16_sub_sat_u                = 0x73;
    case f64x2_ceil                     = 0x74;
    case f64x2_floor                    = 0x75;
    case i8x16_min_s                    = 0x76;
    case i8x16_min_u                    = 0x77;
    case i8x16_max_s                    = 0x78;
    case i8x16_max_u                    = 0x79;
    case f64x2_trunc                    = 0x7a;
    case i8x16_avgr_u                   = 0x7b;
    case i16x8_extadd_pairwise_i8x16_s  = 0x7c;
    case i16x8_extadd_pairwise_i8x16_u  = 0x7d;
    case i32x4_extadd_pairwise_i16x8_s  = 0x7e;
    case i32x4_extadd_pairwise_i16x8_u  = 0x7f;
    case i16x8_abs                      = 0x80;
    case i16x8_neg                      = 0x81;
    case i16x8_q15mulr_sat_s            = 0x82;
    case i16x8_all_true                 = 0x83;
    case i16x8_bitmask                  = 0x84;
    case i16x8_narrow_i32x4_s           = 0x85;
    case i16x8_narrow_i32x4_u           = 0x86;
    case i16x8_extend_low_i8x16_s       = 0x87;
    case i16x8_extend_high_i8x16_s      = 0x88;
    case i16x8_extend_low_i8x16_u       = 0x89;
    case i16x8_extend_high_i8x16_u      = 0x8a;
    case i16x8_shl                      = 0x8b;
    case i16x8_shr_s                    = 0x8c;
    case i16x8_shr_u                    = 0x8d;
    case i16x8_add                      = 0x8e;
    case i16x8_add_sat_s                = 0x8f;
    case i16x8_add_sat_u                = 0x90;
    case i16x8_sub                      = 0x91;
    case i16x8_sub_sat_s                = 0x92;
    case i16x8_sub_sat_u                = 0x93;
    case f64x2_nearest                  = 0x94;
    case i16x8_mul                      = 0x95;
    case i16x8_min_s                    = 0x96;
    case i16x8_min_u                    = 0x97;
    case i16x8_max_s                    = 0x98;
    case i16x8_max_u                    = 0x99;
    // 0x9a reserved
    case i16x8_avgr_u                   = 0x9b;
    case i16x8_extmul_low_i8x16_s       = 0x9c;
    case i16x8_extmul_high_i8x16_s      = 0x9d;
    case i16x8_extmul_low_i8x16_u       = 0x9e;
    case i16x8_extmul_high_i8x16_u      = 0x9f;
    case i32x4_abs                      = 0xa0;
    case i32x4_neg                      = 0xa1;
    // 0xa2 reserved - i8x16.relaxed_swizzle
    case i32x4_all_true                 = 0xa3;
    case i32x4_bitmask                  = 0xa4;
    // 0xa5 reserved - i32x4.relaxed_trunc_f32x4_s
    // 0xa6 reserved - i32x4.relaxed_trunc_f32x4_u
    case i32x4_extend_low_i16x8_s       = 0xa7;
    case i32x4_extend_high_i16x8_s      = 0xa8;
    case i32x4_extend_low_i16x8_u       = 0xa9;
    case i32x4_extend_high_i16x8_u      = 0xaa;
    case i32x4_shl                      = 0xab;
    case i32x4_shr_s                    = 0xac;
    case i32x4_shr_u                    = 0xad;
    case i32x4_add                      = 0xae;
    // 0xaf reserved - f32x4.relaxed_madd
    // 0xb0 reserved - f32x4.relaxed_nmadd
    case i32x4_sub                      = 0xb1;
    // 0xb2 reserved - i8x16.relaxed_laneselect
    // 0xb3 reserved - i16x8.relaxed_laneselect
    // 0xb4 reserved - f32x4.relaxed_min
    case i32x4_mul                      = 0xb5;
    case i32x4_min_s                    = 0xb6;
    case i32x4_min_u                    = 0xb7;
    case i32x4_max_s                    = 0xb8;
    case i32x4_max_u                    = 0xb9;
    case i32x4_dot_i16x8_s              = 0xba;
    // 0xbb reserved
    case i32x4_extmul_low_i16x8_s       = 0xbc;
    case i32x4_extmul_high_i16x8_s      = 0xbd;
    case i32x4_extmul_low_i16x8_u       = 0xbe;
    case i32x4_extmul_high_i16x8_u      = 0xbf;
    case i64x2_abs                      = 0xc0;
    case i64x2_neg                      = 0xc1;
    // 0xc2 reserved
    case i64x2_all_true                 = 0xc3;
    case i64x2_bitmask                  = 0xc4;
    // 0xc5 reserved - i32x4.relaxed_trunc_f64x2_s_zero
    // 0xc6 reserved - i32x4.relaxed_trunc_f64x2_u_zero
    case i64x2_extend_low_i32x4_s       = 0xc7;
    case i64x2_extend_high_i32x4_s      = 0xc8;
    case i64x2_extend_low_i32x4_u       = 0xc9;
    case i64x2_extend_high_i32x4_u      = 0xca;
    case i64x2_shl                      = 0xcb;
    case i64x2_shr_s                    = 0xcc;
    case i64x2_shr_u                    = 0xcd;
    case i64x2_add                      = 0xce;
    // 0xcf reserved - f64x2.relaxed_madd
    // 0xd0 reserved - f64x2.relaxed_nmadd
    case i64x2_sub                      = 0xd1;
    // 0xd2 reserved - i32x4.relaxed_laneselect
    // 0xd3 reserved - i64x2.relaxed_laneselect
    // 0xd4 reserved - f64x2.relaxed_min
    case i64x2_mul                      = 0xd5;
    case i64x2_eq                       = 0xd6;
    case i64x2_ne                       = 0xd7;
    case i64x2_lt_s                     = 0xd8;
    case i64x2_gt_s                     = 0xd9;
    case i64x2_le_s                     = 0xda;
    case i64x2_ge_s                     = 0xdb;
    case i64x2_extmul_low_i32x4_s       = 0xdc;
    case i64x2_extmul_high_i32x4_s      = 0xdd;
    case i64x2_extmul_low_i32x4_u       = 0xde;
    case i64x2_extmul_high_i32x4_u      = 0xdf;
    case f32x4_abs                      = 0xe0;
    case f32x4_neg                      = 0xe1;
    // 0xe2 reserved - f32x4.relaxed_max
    case f32x4_sqrt                     = 0xe3;
    case f32x4_add                      = 0xe4;
    case f32x4_sub                      = 0xe5;
    case f32x4_mul                      = 0xe6;
    case f32x4_div                      = 0xe7;
    case f32x4_min                      = 0xe8;
    case f32x4_max                      = 0xe9;
    case f32x4_pmin                     = 0xea;
    case f32x4_pmax                     = 0xeb;
    case f64x2_abs                      = 0xec;
    case f64x2_neg                      = 0xed;
    // 0xee reserved - f64x2.relaxed_max
    case f64x2_sqrt                     = 0xef;
    case f64x2_add                      = 0xf0;
    case f64x2_sub                      = 0xf1;
    case f64x2_mul                      = 0xf2;
    case f64x2_div                      = 0xf3;
    case f64x2_min                      = 0xf4;
    case f64x2_max                      = 0xf5;
    case f64x2_pmin                     = 0xf6;
    case f64x2_pmax                     = 0xf7;
    case i32x4_trunc_sat_f32x4_s        = 0xf8;
    case i32x4_trunc_sat_f32x4_u        = 0xf9;
    case f32x4_convert_i32x4_s          = 0xfa;
    case f32x4_convert_i32x4_u          = 0xfb;
    case i32x4_trunc_sat_f64x2_s_zero   = 0xfc;
    case i32x4_trunc_sat_f64x2_u_zero   = 0xfd;
    case f64x2_convert_low_i32x4_s      = 0xfe;
    case f64x2_convert_low_i32x4_u      = 0xff;
}
