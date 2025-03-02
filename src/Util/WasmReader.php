<?php

namespace Oatmael\WasmPhp\Util;

use Exception;
use Oatmael\WasmPhp\Type\Export;
use Oatmael\WasmPhp\Type\Extern;
use Oatmael\WasmPhp\Type\F32;
use Oatmael\WasmPhp\Type\F64;
use Oatmael\WasmPhp\Type\Func;
use Oatmael\WasmPhp\Type\I32;
use Oatmael\WasmPhp\Type\I64;
use Oatmael\WasmPhp\Type\Import;
use Oatmael\WasmPhp\Type\Vec;

enum ValueType: int {
    case I32       = 0x7F;
    case I64       = 0x7E;
    case F32       = 0x7D;
    case F64       = 0x7C;
    case VEC       = 0x7B;
    case FUNCREF   = 0x70;
    case EXTERNREF = 0x6F;
}

enum Type: int {
    case FUNCTION = 0x60;
}

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
}

enum ImportType: int {
    case FUNCTION = 0x00;
    case TABLE    = 0x01;
    case MEMORY   = 0x02;
    case GLOBAL   = 0x03;
}
enum ExportType: int {
    case FUNCTION = 0x00;
    case TABLE    = 0x01;
    case MEMORY   = 0x02;
    case GLOBAL   = 0x03;
}

enum Opcode: int {
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
    case i64_ls_u               = 0x58;
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

    // TODO:
    // 0xfc onwards are multibyte instructions

}

class WasmReader {
    
    public const WASM_BINARY_MAGIC = "\0asm";

    // hex length of a uint32
    protected const UINT_32_LEN = 8;

    protected int $version;
    protected array $types;
    protected array $codes;
    protected array $functions;
    protected array $memory;
    protected array $data;
    protected array $exports;
    protected array $imports;

    protected string $wasm;

    public function __construct(string $wasm)
    {
        $this->version = 0;
        $this->types = [];
        $this->codes = [];
        $this->functions = [];
        $this->memory = [];
        $this->data = [];
        $this->exports = [];
        $this->imports = [];

        $this->wasm = bin2hex($wasm);
    }

    public function read()
    {
        $offset = self::readHeader();
        while ($offset < strlen($this->wasm)) {
            $offset = $this->readSection($offset);
        }

        return '';
    }
    
    // https://webassembly.github.io/spec/core/binary/modules.html#binary-module
    protected function readHeader() {
        // Magic binary header to represent the file type
        $magic = bin2hex(pack('a4', self::WASM_BINARY_MAGIC));

        $header = substr($this->wasm, 0, strlen($magic));
        if ($header !== $magic) {
            throw new Exception('Invalid WASM');
        }

        $offset = strlen($magic);
        $version = $this->readUint32($offset);
        if ($version !== 1) {
            throw new Exception('Unsupported WASM version');
        }

        $this->version = $version;

        return $offset;
    }

    protected function readSection(int $offset) {
        $type = Section::from($this->readUint8($offset));
        // Section size is measured as a uint32 of the number of bytes the section takes up
        // * 2 here puts this number in the number of hex chars (so we can substr the input appropriately)
        $section_size = $this->readLEB128Uint32($offset) * 2;

        switch ($type) {
            // https://webassembly.github.io/spec/core/binary/modules.html#binary-customsec
            case Section::CUSTOM:
                // This section is skipped. Not really sure how you'd implement it
                break;
            // https://webassembly.github.io/spec/core/binary/modules.html#type-section
            case Section::TYPE:
                var_dump('Section type');
                $types = $this->readTypeSection($offset, $section_size);
                var_dump($types);
                $this->types = [...$this->types, ...$types];
                break;
            // https://webassembly.github.io/spec/core/binary/modules.html#import-section
            case Section::IMPORT:
                var_dump('Section import');
                $imports = $this->readImportSection($offset, $section_size);
                var_dump($imports);
                $this->imports = [...$this->imports, ...$imports];
                break;
            // https://webassembly.github.io/spec/core/binary/modules.html#function-section
            case Section::FUNCTION:
                var_dump('Section function');
                $funcs = $this->readFunctionSection($offset, $section_size);
                var_dump($funcs);
                $this->functions = [...$this->functions, ...$funcs];
                break;
            // https://webassembly.github.io/spec/core/binary/modules.html#table-section
            case Section::TABLE:
                var_dump('Section table');
                break;
            // https://webassembly.github.io/spec/core/binary/modules.html#memory-section
            case Section::MEMORY:
                var_dump('Section memory');
                break;
            // https://webassembly.github.io/spec/core/binary/modules.html#global-section
            case Section::GLOBAL:
                var_dump('Section global');
                break;
            // https://webassembly.github.io/spec/core/binary/modules.html#export-section
            case Section::EXPORT:
                var_dump('Section export');
                $exports = $this->readExportSection($offset, $section_size);
                var_dump($exports);
                $this->exports = [...$this->exports, ...$exports];
                break;
            // https://webassembly.github.io/spec/core/binary/modules.html#start-section
            case Section::START:
                var_dump('Section start');
                break;
            // https://webassembly.github.io/spec/core/binary/modules.html#element-section
            case Section::ELEMENT:
                var_dump('Section element');
                break;
            // https://webassembly.github.io/spec/core/binary/modules.html#element-section
            case Section::CODE:
                var_dump('Section code');
                break;
            // https://webassembly.github.io/spec/core/binary/modules.html#data-section
            case Section::DATA:
                var_dump('Section data');
                break;
            // https://webassembly.github.io/spec/core/binary/modules.html#data-count-section
            case Section::DATA_COUNT:
                var_dump('Section data count');
                // throw new Exception('Data count sections are not supported');
                break;
            default:
                throw new Exception('Invalid section type: ' . $type);
        }

        return $offset + $section_size;
    }

    protected function readTypeSection(int $offset, int $section_size) {
        $final = $offset + $section_size;

        $vec_size = $this->readLEB128Uint32($offset);
        $types = [];

        $read_offset = $offset;
        while ($read_offset < $final) {
            if (count($types) > $vec_size) {
                throw new Exception('Malformed type section - vec size overflow');
            }

            // Unused - WASM v1 only has one type
            $current_type = Type::from($this->readUint8($read_offset));

            $params = [];
            $results = [];

            $num_params = $this->readLEB128Uint32($read_offset);
            for ($i = 0; $i < $num_params; $i++) {
                $params[] = ValueType::from($this->readUint8($read_offset));
            }

            $num_results = $this->readLEB128Uint32($read_offset);
            for ($i = 0; $i < $num_results; $i++) {
                $results[] = ValueType::from($this->readUint8($read_offset));
            }

            $types[] = new Func($params, $results);
        }

        return $types;
    }

    protected function readImportSection(int $offset, int $section_size) {
        $final = $offset + $section_size;

        $vec_size = $this->readLEB128Uint32($offset);
        $imports = [];

        $read_offset = $offset;
        while ($read_offset < $final) {
            if (count($imports) > $vec_size) {
                throw new Exception('Malformed type section - vec size overflow');
            }

            $module = $this->readName($read_offset);
            $field = $this->readName($read_offset);
            $import_type = ImportType::from($this->readUint8($read_offset));

            switch ($import_type) {
                case ImportType::FUNCTION:
                    $function_idx = $this->readLEB128Uint32($read_offset);
                    $imports[] = new Import($module, $field, $function_idx);
                    break;
                // TODO:
                case ImportType::TABLE:
                case ImportType::MEMORY:
                case ImportType::GLOBAL:
                default:
                    throw new Exception('Unsupported import type');
            }
        }

        return $imports;
    }

    protected function readFunctionSection(int $offset, int $section_size) {
        $final = $offset + $section_size;

        $vec_size = $this->readLEB128Uint32($offset);
        $funcs = [];

        $read_offset = $offset;
        while ($read_offset < $final) {
            if (count($funcs) > $vec_size) {
                throw new Exception('Malformed function section - vec size overflow');
            }

            $funcs[] = $this->readLEB128Uint32($read_offset);
        }

        return $funcs;
    }

    protected function readExportSection(int $offset, int $section_size) {
        $final = $offset + $section_size;

        $vec_size = $this->readLEB128Uint32($offset);
        $exports = [];

        $read_offset = $offset;
        while ($read_offset < $final) {
            if (count($exports) > $vec_size) {
                throw new Exception('Malformed function section - vec size overflow');
            }

            $name = $this->readName($read_offset);
            $export_type = ExportType::from($this->readUint8($read_offset));

            switch ($export_type) {
                case ExportType::FUNCTION:
                    $function_idx = $this->readLEB128Uint32($read_offset);
                    $exports[] = new Export($name, $function_idx);
                    break;
                // TODO:
                case ExportType::TABLE:
                case ExportType::MEMORY:
                case ExportType::GLOBAL:
                default:
                    throw new Exception('Unsupported export type ' . $export_type);
            }
        }

        return $exports;
    }

    protected function readName(int &$offset): string
    {
        $size = $this->readLEB128Uint32($offset);
        $name_hex = substr($this->wasm, $offset, $size * 2);
        $offset += $size * 2;
        if (!mb_check_encoding(hex2bin($name_hex), 'UTF-8')) {
            throw new Exception('Invalid UTF-8 encoded string');
        }

        return hex2bin($name_hex);
    }
    
    protected function readUint8(int &$offset) {
        $result = unpack('C', hex2bin(substr($this->wasm, $offset, 2)));
        $offset += 2;
        return $result[1];
    }
    
    protected function readUint32(int &$offset) {
        $result = unpack('V', hex2bin(substr($this->wasm, $offset, 8)));
        $offset += 8;
        return $result[1];
    }

    protected function readLEB128Uint32(int &$offset) {
        // Unpack the entire string into an array of bytes (unsigned chars)
        $bytes = unpack('C*', hex2bin(substr($this->wasm, $offset)));
        
        $result = 0;
        $shift = 0;
        
        foreach ($bytes as $byte) {
            $result |= (($byte & 0x7F) << $shift);
            $offset += 2;
            
            // If the most significant bit is 0, this is the final byte.
            if (($byte & 0x80) === 0) {
                return $result;
            }
            
            $shift += 7;
            if ($shift >= 32) {
                throw new Exception("LEB128 value too large for a u32");
            }
        }
        
        throw new Exception("Incomplete LEB128 sequence: termination byte not found");
    }
}