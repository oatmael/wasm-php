<?php

namespace Oatmael\WasmPhp\Instruction;

use Attribute;
use Exception;
use Oatmael\WasmPhp\Util\WasmReader;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionAttribute;
use ReflectionClass;
use RegexIterator;

#[Attribute(Attribute::TARGET_CLASS)]
class Opcode {
    public function __construct(public readonly OpcodeEnum $opcode) {}

    public static function findClass(string $namespace, OpcodeEnum $opcode) {
        $baseDir = __DIR__;

        // Ensure directory path ends with separator
        $baseDir = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        // Find all PHP files in the directory
        $directory = new RecursiveDirectoryIterator($baseDir);
        $iterator = new RecursiveIteratorIterator($directory);
        $phpFiles = new RegexIterator($iterator, '/^.+\.php$/i');

        foreach ($phpFiles as $phpFile) {
            if (realpath($phpFile->getRealPath()) === realpath(__FILE__)) {
                continue;
            }

            // Convert file path to class name within the namespace
            $relativePath = str_replace($baseDir, '', $phpFile->getRealPath());
            $relativePath = str_replace('.php', '', $relativePath);
            $relativePath = str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath);

            $className = $namespace . '\\' . $relativePath;

            // Check if class exists and can be loaded
            if (!class_exists($className)) {
                continue;
            }

            // Get reflection of the class
            $reflectionClass = new ReflectionClass($className);
            $opcodes = $reflectionClass->getAttributes(self::class, ReflectionAttribute::IS_INSTANCEOF);

            if (
                $reflectionClass->isAbstract() ||
                $reflectionClass->isInterface() ||
                !$reflectionClass->implementsInterface(InstructionInterface::class) ||
                empty($opcodes)
            ) {
                continue;
            }

            foreach ($opcodes as $attribute) {
                $instance = $attribute->newInstance();
                if ($instance->opcode === $opcode) {
                    return $className;
                }
            }
        }

        return null;
    }

    public static function readOpcode(string $input, int &$offset): InstructionInterface {
        $opcode = WasmReader::readUint8($input, $offset);
        if (StandardOpcode::from($opcode)) {
            $offset -= 2;
        }

        return match ($opcode) {
            ExtensionOpcode::PREFIX => ExtensionOpcode::readOpcode($input, $offset),
            SimdOpcode::PREFIX => SimdOpcode::readOpcode($input, $offset),
            default => StandardOpcode::readOpcode($input, $offset),
        };
    }
}

interface OpcodeEnum
{
    public static function readOpcode(string $input, int &$offset): InstructionInterface;
}

enum StandardOpcode: int implements OpcodeEnum {
    case unreachable            = 0x00;
    case nop                    = 0x01;
    case block                  = 0x02;
    case loop                   = 0x03;
    case if                     = 0x04;
    case else                   = 0x05;
    // 0x06 -> 0x0a reserved
    case end                    = 0x0b;
    case br                     = 0x0c;
    case br_if                  = 0x0d;
    case br_table               = 0x0e;
    case return                 = 0x0f;
    case call                   = 0x10;
    case call_indirect          = 0x11;
    // 0x12 -> 0x19 reserved
    case drop                   = 0x1a;
    case select                 = 0x1b;
    case select_t               = 0x1c;
    // 0x1d -> 0x1f reserved
    case local_get              = 0x20;
    case local_set              = 0x21;
    case local_tee              = 0x22;
    case global_get             = 0x23;
    case global_set             = 0x24;
    case table_get              = 0x25;
    case table_set              = 0x26;
    // 0x27 reserved
    case i32_load               = 0x28;
    case i64_load               = 0x29;
    case f32_load               = 0x2a;
    case f64_load               = 0x2b;
    case i32_load8_s            = 0x2c;
    case i32_load8_u            = 0x2d;
    case i32_load16_s           = 0x2e;
    case i32_load16_u           = 0x2f;
    case i64_load8_s            = 0x30;
    case i64_load8_u            = 0x31;
    case i64_load16_s           = 0x32;
    case i64_load16_u           = 0x33;
    case i64_load32_s           = 0x34;
    case i64_load32_u           = 0x35;
    case i32_store              = 0x36;
    case i64_store              = 0x37;
    case f32_store              = 0x38;
    case f64_store              = 0x39;
    case i32_store8             = 0x3a;
    case i32_store16            = 0x3b;
    case i64_store8             = 0x3c;
    case i64_store16            = 0x3d;
    case i64_store32            = 0x3e;
    case memory_size            = 0x3f;
    case memory_grow            = 0x40;
    case i32_const              = 0x41;
    case i64_const              = 0x42;
    case f32_const              = 0x43;
    case f64_const              = 0x44;
    case i32_eqz                = 0x45;
    case i32_eq                 = 0x46;
    case i32_ne                 = 0x47;
    case i32_lt_s               = 0x48;
    case i32_lt_u               = 0x49;
    case i32_gt_s               = 0x4a;
    case i32_gt_u               = 0x4b;
    case i32_le_s               = 0x4c;
    case i32_le_u               = 0x4d;
    case i32_ge_s               = 0x4e;
    case i32_ge_u               = 0x4f;
    case i64_eqz                = 0x50;
    case i64_eq                 = 0x51;
    case i64_ne                 = 0x52;
    case i64_lt_s               = 0x53;
    case i64_lt_u               = 0x54;
    case i64_gt_s               = 0x55;
    case i64_gt_u               = 0x56;
    case i64_le_s               = 0x57;
    case i64_le_u               = 0x58;
    case i64_ge_s               = 0x59;
    case i64_ge_u               = 0x5a;
    case f32_eq                 = 0x5b;
    case f32_ne                 = 0x5c;
    case f32_lt                 = 0x5d;
    case f32_gt                 = 0x5e;
    case f32_le                 = 0x5f;
    case f32_ge                 = 0x60;
    case f64_eq                 = 0x61;
    case f64_ne                 = 0x62;
    case f64_lt                 = 0x63;
    case f64_gt                 = 0x64;
    case f64_le                 = 0x65;
    case f64_ge                 = 0x66;
    case i32_clz                = 0x67;
    case i32_ctz                = 0x68;
    case i32_popcnt             = 0x69;
    case i32_add                = 0x6a;
    case i32_sub                = 0x6b;
    case i32_mul                = 0x6c;
    case i32_div_s              = 0x6d;
    case i32_div_u              = 0x6e;
    case i32_rem_s              = 0x6f;
    case i32_rem_u              = 0x70;
    case i32_and                = 0x71;
    case i32_or                 = 0x72;
    case i32_xor                = 0x73;
    case i32_shl                = 0x74;
    case i32_shr_s              = 0x75;
    case i32_shr_u              = 0x76;
    case i32_rotl               = 0x77;
    case i32_rotr               = 0x78;
    case i64_clz                = 0x79;
    case i64_ctz                = 0x7a;
    case i64_popcnt             = 0x7b;
    case i64_add                = 0x7c;
    case i64_sub                = 0x7d;
    case i64_mul                = 0x7e;
    case i64_div_s              = 0x7f;
    case i64_div_u              = 0x80;
    case i64_rem_s              = 0x81;
    case i64_rem_u              = 0x82;
    case i64_and                = 0x83;
    case i64_or                 = 0x84;
    case i64_xor                = 0x85;
    case i64_shl                = 0x86;
    case i64_shr_s              = 0x87;
    case i64_shr_u              = 0x88;
    case i64_rotl               = 0x89;
    case i64_rotr               = 0x8a;
    case f32_abs                = 0x8b;
    case f32_neg                = 0x8c;
    case f32_ceil               = 0x8d;
    case f32_floor              = 0x8e;
    case f32_trunc              = 0x8f;
    case f32_nearest            = 0x90;
    case f32_sqrt               = 0x91;
    case f32_add                = 0x92;
    case f32_sub                = 0x93;
    case f32_mul                = 0x94;
    case f32_div                = 0x95;
    case f32_min                = 0x96;
    case f32_max                = 0x97;
    case f32_copysign           = 0x98;
    case f64_abs                = 0x99;
    case f64_neg                = 0x9a;
    case f64_ceil               = 0x9b;
    case f64_floor              = 0x9c;
    case f64_trunc              = 0x9d;
    case f64_nearest            = 0x9e;
    case f64_sqrt               = 0x9f;
    case f64_add                = 0xa0;
    case f64_sub                = 0xa1;
    case f64_mul                = 0xa2;
    case f64_div                = 0xa3;
    case f64_min                = 0xa4;
    case f64_max                = 0xa5;
    case f64_copysign           = 0xa6;
    case i32_wrap_i64           = 0xa7;
    case i32_trunc_f32_s        = 0xa8;
    case i32_trunc_f32_u        = 0xa9;
    case i32_trunc_f64_s        = 0xaa;
    case i32_trunc_f64_u        = 0xab;
    case i64_extend_i32_s       = 0xac;
    case i64_extend_i32_u       = 0xad;
    case i64_trunc_f32_s        = 0xae;
    case i64_trunc_f32_u        = 0xaf;
    case i64_trunc_f64_s        = 0xb0;
    case i64_trunc_f64_u        = 0xb1;
    case f32_convert_i32_s      = 0xb2;
    case f32_convert_i32_u      = 0xb3;
    case f32_convert_i64_s      = 0xb4;
    case f32_convert_i64_u      = 0xb5;
    case f32_demote_f64         = 0xb6;
    case f64_convert_i32_s      = 0xb7;
    case f64_convert_i32_u      = 0xb8;
    case f64_convert_i64_s      = 0xb9;
    case f64_convert_i64_u      = 0xba;
    case f64_promote_f32        = 0xbb;
    case i32_reinterpret_f32    = 0xbc;
    case i64_reinterpret_f64    = 0xbd;
    case f32_reinterpret_i32    = 0xbe;
    case f64_reinterpret_i64    = 0xbf;
    case i32_extend8_s          = 0xc0;
    case i32_extend16_s         = 0xc1;
    case i64_extend8_s          = 0xc2;
    case i64_extend16_s         = 0xc3;
    case i64_extend32_s         = 0xc4;
    // 0xc5 -> 0xcf reserved
    case ref_null               = 0xd0;
    case ref_is_null            = 0xd1;
    case ref_func               = 0xd2;
    // 0xd3 -> 0xfb reserved

    public static function readOpcode(string $input, int &$offset): InstructionInterface
    {
        $opcode = WasmReader::readUint8($input, $offset);
        $opcode = self::from($opcode);
        $instruction_class = Opcode::findClass(__NAMESPACE__, $opcode);
        if (!$instruction_class){
            throw new Exception('No implementation for opcode ' . $opcode->name);
        }

        return $instruction_class::fromInput($input, $offset);
    }
}

// Extension & SIMD codes are read with LEB

// 0xfc extension codes
enum ExtensionOpcode: int implements OpcodeEnum {
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

    public static function readOpcode(string $input, int &$offset): InstructionInterface
    {
        $opcode = WasmReader::readLEB128Uint32($input, $offset);
        $opcode = self::from($opcode);
        $instruction_class = Opcode::findClass(__NAMESPACE__ .'\\Extension', $opcode);
        if (!$instruction_class){
            throw new Exception('No implementation for opcode ' . $opcode->name);
        }

        return $instruction_class::fromInput($input, $offset);
    }
}

// 0xfd simd codes
enum SimdOpcode: int implements OpcodeEnum {
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

    public static function readOpcode(string $input, int &$offset): InstructionInterface
    {
        $opcode = WasmReader::readLEB128Uint32($input, $offset);
        $opcode = self::from($opcode);
        $instruction_class = Opcode::findClass(__NAMESPACE__ .'\\Simd', $opcode);
        if (!$instruction_class){
            throw new Exception('No implementation for opcode ' . $opcode->name);
        }

        return $instruction_class::fromInput($input, $offset);
    }
}