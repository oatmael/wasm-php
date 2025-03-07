<?php

namespace Oatmael\WasmPhp\Util;

use Exception;
use Oatmael\WasmPhp\Instruction\Opcode;
use Oatmael\WasmPhp\Module;
use Oatmael\WasmPhp\Type\Code;
use Oatmael\WasmPhp\Type\Export;
use Oatmael\WasmPhp\Type\Func;
use Oatmael\WasmPhp\Type\Import;
use Oatmael\WasmPhp\Type\Local;

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


class WasmReader {
    
    public const WASM_BINARY_MAGIC = "\0asm";

    protected int $version;
    protected array $types;
    protected array $codes;
    protected array $functions;
    protected array $memory;
    protected array $data;
    protected array $exports;
    protected array $imports;

    protected string $wasm;

    public function __construct()
    {
    }

    public function read(string $wasm)
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

        $offset = self::readHeader();
        while ($offset < strlen($this->wasm)) {
            $offset = $this->readSection($offset);
        }

        return new Module(
            $this->version,
            $this->types,
            $this->codes,
            $this->functions,
            $this->memory,
            $this->data,
            $this->exports,
            $this->imports
        );
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
        $version = self::readUint32($this->wasm, $offset);
        if ($version !== 1) {
            throw new Exception('Unsupported WASM version');
        }

        $this->version = $version;

        return $offset;
    }

    protected function readSection(int $offset) {
        $type = Section::from(self::readUint8($this->wasm, $offset));
        // Section size is measured as a uint32 of the number of bytes the section takes up
        // * 2 here puts this number in the number of hex chars (so we can substr the input appropriately)
        $section_size = self::readLEB128Uint32($this->wasm, $offset) * 2;

        switch ($type) {
            // https://webassembly.github.io/spec/core/binary/modules.html#binary-customsec
            case Section::CUSTOM:
                // This section is skipped. Not really sure how you'd implement it
                break;
            // https://webassembly.github.io/spec/core/binary/modules.html#type-section
            case Section::TYPE:
                $types = $this->readTypeSection($offset, $section_size);
                $this->types = [...$this->types, ...$types];
                break;
            // https://webassembly.github.io/spec/core/binary/modules.html#import-section
            case Section::IMPORT:
                $imports = $this->readImportSection($offset, $section_size);
                $this->imports = [...$this->imports, ...$imports];
                break;
            // https://webassembly.github.io/spec/core/binary/modules.html#function-section
            case Section::FUNCTION:
                $funcs = $this->readFunctionSection($offset, $section_size);
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
                $exports = $this->readExportSection($offset, $section_size);
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
            // https://webassembly.github.io/spec/core/binary/modules.html#code-section
            case Section::CODE:
                $codes = $this->readCodeSection($offset, $section_size);
                $this->codes = [...$this->codes, ...$codes];
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

        $vec_size = self::readLEB128Uint32($this->wasm, $offset);
        $types = [];

        $read_offset = $offset;
        while ($read_offset < $final) {
            if ($vec_size > 0 && count($types) > $vec_size) {
                throw new Exception('Malformed type section - vec size overflow');
            }

            // Unused - WASM v1 only has one type
            $current_type = Type::from(self::readUint8($this->wasm, $read_offset));

            $params = [];
            $results = [];

            $num_params = self::readLEB128Uint32($this->wasm, $read_offset);
            for ($i = 0; $i < $num_params; $i++) {
                $params[] = ValueType::from(self::readUint8($this->wasm, $read_offset));
            }

            $num_results = self::readLEB128Uint32($this->wasm, $read_offset);
            for ($i = 0; $i < $num_results; $i++) {
                $results[] = ValueType::from(self::readUint8($this->wasm, $read_offset));
            }

            $types[] = new Func($params, $results);
        }

        return $types;
    }

    protected function readImportSection(int $offset, int $section_size) {
        $final = $offset + $section_size;

        $vec_size = self::readLEB128Uint32($this->wasm, $offset);
        $imports = [];

        $read_offset = $offset;
        while ($read_offset < $final) {
            if ($vec_size > 0 && count($imports) > $vec_size) {
                throw new Exception('Malformed type section - vec size overflow');
            }

            $module = self::readName($this->wasm, $read_offset);
            $field = self::readName($this->wasm, $read_offset);
            $import_type = ImportType::from(self::readUint8($this->wasm, $read_offset));

            switch ($import_type) {
                case ImportType::FUNCTION:
                    $function_idx = self::readLEB128Uint32($this->wasm, $read_offset);
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

        $vec_size = self::readLEB128Uint32($this->wasm, $offset);
        $funcs = [];

        $read_offset = $offset;
        while ($vec_size > 0 && $read_offset < $final) {
            if (count($funcs) > $vec_size) {
                throw new Exception('Malformed function section - vec size overflow');
            }

            $funcs[] = self::readLEB128Uint32($this->wasm, $read_offset);
        }

        return $funcs;
    }

    protected function readExportSection(int $offset, int $section_size) {
        $final = $offset + $section_size;

        $vec_size = self::readLEB128Uint32($this->wasm, $offset);
        $exports = [];

        $read_offset = $offset;
        while ($read_offset < $final) {
            if ($vec_size > 0 && count($exports) > $vec_size) {
                throw new Exception('Malformed function section - vec size overflow');
            }

            $name = self::readName($this->wasm, $read_offset);
            $export_type = ExportType::from(self::readUint8($this->wasm, $read_offset));

            switch ($export_type) {
                case ExportType::FUNCTION:
                    $function_idx = self::readLEB128Uint32($this->wasm, $read_offset);
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

    protected function readCodeSection(int $offset, int $section_size) {
        $final = $offset + $section_size;

        $vec_size = self::readLEB128Uint32($this->wasm, $offset);
        $codes = [];

        $read_offset = $offset;
        while ($read_offset < $final) {
            if ($vec_size > 0 && count($codes) > $vec_size) {
                throw new Exception('Malformed function section - vec size overflow');
            }

            $body_size = self::readLEB128Uint32($this->wasm, $read_offset);
            $instructions_final = $read_offset + ($body_size * 2);

            $vec_size = self::readLEB128Uint32($this->wasm, $read_offset);
            $locals_final = $read_offset + ($vec_size * 2);
            $locals = [];

            while ($read_offset < $locals_final) {
                if (count($locals) > $vec_size) {
                    throw new Exception('Malformed code body - vec size overflow');
                }

                $type_count = self::readLEB128Uint32($this->wasm, $read_offset);
                $local_type = ValueType::from(self::readUint8($this->wasm, $read_offset));

                $locals[] = new Local($type_count, $local_type);
            }

            $instructions = [];
            while ($read_offset < $instructions_final) {
                $instructions[] = Opcode::readOpcode($this->wasm, $read_offset);
            }

            $codes[] = new Code($locals, $instructions);
        }

        return $codes;
    }

    public static function readName(string $input, int &$offset): string
    {
        $size = self::readLEB128Uint32($input, $offset);
        $name_hex = substr($input, $offset, $size * 2);
        $offset += $size * 2;
        if (!mb_check_encoding(hex2bin($name_hex), 'UTF-8')) {
            throw new Exception('Invalid UTF-8 encoded string');
        }

        return hex2bin($name_hex);
    }
    
    public static function readUint8(string $input, int &$offset) {
        $result = unpack('C', hex2bin(substr($input, $offset, 2)));
        $offset += 2;
        return $result[1];
    }
    
    public static function readUint32(string $input, int &$offset) {
        $result = unpack('V', hex2bin(substr($input, $offset, 8)));
        $offset += 8;
        return $result[1];
    }

    public static function readLEB128Uint32(string $input, int &$offset) {
        // Unpack the entire string into an array of bytes (unsigned chars)
        $bytes = unpack('C*', hex2bin(substr($input, $offset)));
        
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