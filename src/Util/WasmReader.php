<?php

namespace Oatmael\WasmPhp\Util;

use Exception;
use Oatmael\WasmPhp\Instruction\Opcode;
use Oatmael\WasmPhp\Instruction\StandardOpcode;
use Oatmael\WasmPhp\Module;
use Oatmael\WasmPhp\Type\Code;
use Oatmael\WasmPhp\Type\Data;
use Oatmael\WasmPhp\Type\Element;
use Oatmael\WasmPhp\Type\Export;
use Oatmael\WasmPhp\Type\F32;
use Oatmael\WasmPhp\Type\F64;
use Oatmael\WasmPhp\Type\Func;
use Oatmael\WasmPhp\Type\GlobalImmutable;
use Oatmael\WasmPhp\Type\GlobalMutable;
use Oatmael\WasmPhp\Type\I32;
use Oatmael\WasmPhp\Type\I64;
use Oatmael\WasmPhp\Type\Import;
use Oatmael\WasmPhp\Type\Local;
use Oatmael\WasmPhp\Type\Memory;
use Oatmael\WasmPhp\Type\Table;

enum ValueType: int {
    case I32       = 0x7F;
    case I64       = 0x7E;
    case F32       = 0x7D;
    case F64       = 0x7C;
    case VEC       = 0x7B;
    case FUNCREF   = 0x70;
    case EXTERNREF = 0x6F;
    case VOID      = 0x40;
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

enum GlobalMutability: int {
    case Immutable = 0x00;
    case Mutable   = 0x01;
}

class WasmReader {

    public const WASM_BINARY_MAGIC = "\0asm";

    protected int $version;
    protected array $types;
    protected array $imports;
    protected array $functions;
    protected array $tables;
    protected array $memory;
    protected array $globals;
    protected array $exports;
    protected ?int $start;
    protected array $elements;
    protected array $codes;
    protected array $data;
    protected ?int $data_count;

    protected string $wasm;

    public function __construct()
    {
    }

    public function read(string $wasm)
    {
        $this->version = 0;
        $this->start = null;
        $this->types = [];
        $this->imports = [];
        $this->functions = [];
        $this->tables = [];
        $this->memory = [];
        $this->globals = [];
        $this->exports = [];
        $this->elements = [];
        $this->codes = [];
        $this->data = [];

        $this->wasm = bin2hex($wasm);

        $offset = self::readHeader();
        while ($offset < strlen($this->wasm)) {
            $offset = $this->readSection($offset);
        }

        return new Module(
            version:    $this->version,
            types:      $this->types,
            codes:      $this->codes,
            functions:  $this->functions,
            memory:     $this->memory,
            data:       $this->data,
            exports:    $this->exports,
            imports:    $this->imports,
            globals:    $this->globals,
            tables:     $this->tables,
            elements:   $this->elements,
            start:      $this->start,
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

    // https://webassembly.github.io/spec/core/binary/modules.html
    protected function readSection(int $offset) {
        $type = Section::from(self::readUint8($this->wasm, $offset));
        // Section size is measured as a uint32 of the number of bytes the section takes up
        // * 2 here puts this number in the number of hex chars (so we can substr the input appropriately)
        $section_size = self::readLEB128Uint32($this->wasm, $offset) * 2;

        switch ($type) {
            case Section::CUSTOM:
                // https://webassembly.github.io/spec/core/binary/modules.html#binary-customsec
                // This section is skipped. Not really sure how you'd implement it
                break;
            case Section::TYPE:
                $types = $this->readTypeSection($offset, $section_size);
                $this->types = [...$this->types, ...$types];
                break;
            case Section::IMPORT:
                $imports = $this->readImportSection($offset, $section_size);
                $this->imports = [...$this->imports, ...$imports];
                break;
            case Section::FUNCTION:
                $funcs = $this->readFunctionSection($offset, $section_size);
                $this->functions = [...$this->functions, ...$funcs];
                break;
            case Section::TABLE:
                $tables = $this->readTableSection($offset, $section_size);
                $this->tables = [...$this->tables, ...$tables];
                break;
            case Section::MEMORY:
                $memory = $this->readMemorySection($offset, $section_size);
                $this->memory = [...$this->memory, ...$memory];
                break;
            case Section::GLOBAL:
                $globals = $this->readGlobalSection($offset, $section_size);
                $this->globals = [...$this->globals, ...$globals];
                break;
            case Section::EXPORT:
                $exports = $this->readExportSection($offset, $section_size);
                $this->exports = [...$this->exports, ...$exports];
                break;
            case Section::START:
                // https://webassembly.github.io/spec/core/binary/modules.html#start-section
                $read_offset = $offset;
                $this->start = self::readLEB128Uint32($this->wasm, $read_offset);
                break;
            case Section::ELEMENT:
                $elements = $this->readElementSection($offset, $section_size);
                $this->elements = [...$this->elements, ...$elements];
                break;
            case Section::CODE:
                $codes = $this->readCodeSection($offset, $section_size);
                $this->codes = [...$this->codes, ...$codes];
                break;
            case Section::DATA:
                $data = $this->readDataSection($offset, $section_size);
                $this->data = [...$this->data, ...$data];
                break;
            case Section::DATA_COUNT:
                // https://webassembly.github.io/spec/core/binary/modules.html#data-count-section
                $read_offset = $offset;
                $this->data_count = self::readLEB128Uint32($this->wasm, $read_offset);
                break;
            default:
                throw new Exception('Invalid section type: ' . $type);
        }

        return $offset + $section_size;
    }

    // https://webassembly.github.io/spec/core/binary/modules.html#type-section
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

    // https://webassembly.github.io/spec/core/binary/modules.html#import-section
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
            $idx = self::readLEB128Uint32($this->wasm, $read_offset);
            $imports[] = new Import($module, $field, $import_type, $idx);
        }

        return $imports;
    }

    // https://webassembly.github.io/spec/core/binary/modules.html#function-section
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

    // https://webassembly.github.io/spec/core/binary/modules.html#export-section
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
            $idx = self::readLEB128Uint32($this->wasm, $read_offset);
            $exports[] = new Export($name, $export_type, $idx);
        }

        return $exports;
    }

    // https://webassembly.github.io/spec/core/binary/modules.html#code-section
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

            $locals_size = self::readLEB128Uint32($this->wasm, $read_offset);
            $locals_final = $read_offset + ($locals_size * 2);
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

    // https://webassembly.github.io/spec/core/binary/modules.html#memory-section
    protected function readMemorySection(int $offset, int $section_size) {
        $final = $offset + $section_size;

        $vec_size = self::readLEB128Uint32($this->wasm, $offset);
        $memory = [];

        $read_offset = $offset;
        while ($read_offset < $final) {
            if ($vec_size > 0 && count($memory) > $vec_size) {
                throw new Exception('Malformed function section - vec size overflow');
            }

            $flags = self::readLEB128Uint32($this->wasm, $read_offset);
            $min = self::readLEB128Uint32($this->wasm, $read_offset);
            $max = match ($flags) {
                0x00    => null,
                default => self::readLEB128Uint32($this->wasm, $read_offset),
            };

            $memory[] = new Memory($min, $max);
        }

        return $memory;
    }

    // https://webassembly.github.io/spec/core/binary/modules.html#data-section
    protected function readDataSection(int $offset, int $section_size) {
        $final = $offset + $section_size;

        $vec_size = self::readLEB128Uint32($this->wasm, $offset);
        $data = [];

        $read_offset = $offset;
        while ($read_offset < $final) {
            if ($vec_size > 0 && count($data) > $vec_size) {
                throw new Exception('Malformed function section - vec size overflow');
            }

            $memory_idx = self::readLEB128Uint32($this->wasm, $read_offset);
            // The opcode describes the type of const used, but it isn't actually needed
            $opcode = StandardOpcode::from(self::readLEB128Uint32($this->wasm, $read_offset));
            $memory_offset = self::readLEB128Uint32($this->wasm, $read_offset);
            $end_opcode = StandardOpcode::from(self::readLEB128Uint32($this->wasm, $read_offset));
            if ($end_opcode !== StandardOpcode::end) {
                throw new Exception('Invalid data layout');
            }

            $data_size = self::readLEB128Uint32($this->wasm, $read_offset);
            $data[] = new Data(
                $memory_idx,
                $memory_offset,
                init: array_values(unpack("C" . $data_size, hex2bin(substr($this->wasm, $read_offset, $data_size * 2))))
            );

            $read_offset += $data_size * 2;
        }

        return $data;
    }

    // https://webassembly.github.io/spec/core/binary/modules.html#table-section
    protected function readTableSection(int $offset, int $section_size) {
        $final = $offset + $section_size;

        $vec_size = self::readLEB128Uint32($this->wasm, $offset);
        $tables = [];

        $read_offset = $offset;
        while ($read_offset < $final) {
            if ($vec_size > 0 && count($tables) > $vec_size) {
                throw new Exception('Malformed function section - vec size overflow');
            }

            $element_type = ValueType::from(self::readLEB128Uint32($this->wasm, $read_offset));
            $flags = self::readLEB128Uint32($this->wasm, $read_offset);
            $min = self::readLEB128Uint32($this->wasm, $read_offset);
            $max = match ($flags) {
                0x00    => null,
                default => self::readLEB128Uint32($this->wasm, $read_offset),
            };

            $tables[] = new Table($element_type, $min, $max);
        }

        return $tables;
    }

    // https://webassembly.github.io/spec/core/binary/modules.html#element-section
    protected function readElementSection(int $offset, int $section_size) {
        $final = $offset + $section_size;

        $vec_size = self::readLEB128Uint32($this->wasm, $offset);
        $elements = [];

        $read_offset = $offset;
        while ($read_offset < $final) {
            if ($vec_size > 0 && count($elements) > $vec_size) {
                throw new Exception('Malformed function section - vec size overflow');
            }

            $table_idx = self::readLEB128Uint32($this->wasm, $read_offset);
            // The opcode describes the type of const used, but it isn't actually needed
            $opcode = StandardOpcode::from(self::readLEB128Uint32($this->wasm, $read_offset));
            $table_offset = self::readLEB128Uint32($this->wasm, $read_offset);
            $end_opcode = StandardOpcode::from(self::readLEB128Uint32($this->wasm, $read_offset));
            if ($end_opcode !== StandardOpcode::end) {
                throw new Exception('Invalid element layout');
            }

            $func_vec_size = self::readLEB128Uint32($this->wasm, $read_offset);
            $function_indices = [];
            for ($i = 0; $i < $func_vec_size; $i++) {
                $function_indices[] = self::readLEB128Uint32($this->wasm, $read_offset);
            }

            $elements[] = new Element(
                $table_idx,
                $table_offset,
                $function_indices
            );
        }

        return $elements;
    }

    // https://webassembly.github.io/spec/core/binary/modules.html#global-section
    protected function readGlobalSection(int $offset, int $section_size) {
        $final = $offset + $section_size;

        $vec_size = self::readLEB128Uint32($this->wasm, $offset);
        $globals = [];

        $read_offset = $offset;
        while ($read_offset < $final) {
            if ($vec_size > 0 && count($globals) > $vec_size) {
                throw new Exception('Malformed function section - vec size overflow');
            }

            $value_type = ValueType::from(self::readLEB128Uint32($this->wasm, $read_offset));
            $mutability = GlobalMutability::from(self::readLEB128Uint32($this->wasm, $read_offset));

            // The opcode describes the type of const used, but it isn't actually needed
            $opcode = StandardOpcode::from(self::readLEB128Uint32($this->wasm, $read_offset));
            $value = match ($value_type) {
                ValueType::I32 => new I32(self::readLEB128Uint32($this->wasm, $read_offset)),
                ValueType::I64 => new I64(self::readLEB128Uint32($this->wasm, $read_offset)),
                ValueType::F32 => new F32(self::readLEB128Uint32($this->wasm, $read_offset)),
                ValueType::F64 => new F64(self::readLEB128Uint32($this->wasm, $read_offset)),
            };
            $end_opcode = StandardOpcode::from(self::readLEB128Uint32($this->wasm, $read_offset));
            if ($end_opcode !== StandardOpcode::end) {
                throw new Exception('Invalid data layout');
            }

            $global = $mutability === GlobalMutability::Immutable ?
                new GlobalImmutable($value_type, $value) :
                new GlobalMutable($value_type, $value);

            $globals[] = $global;
        }

        return $globals;
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

    public static function readLEB128int32(string $input, int &$offset) {
        // Unpack the entire string into an array of bytes (unsigned chars)
        $bytes = unpack('C*', hex2bin(substr($input, $offset)));

        $result = 0;
        $shift = 0;

        foreach ($bytes as $byte) {
            $result |= (($byte & 0x7F) << $shift);
            $offset += 2;

            // If the most significant bit is 0, this is the final byte.
            if (($byte & 0x80) === 0) {
                if ($shift < 32 && ($byte & 0x40)) {
                    $result |= (~0 << ($shift + 7));
                }
                return $result;
            }

            $shift += 7;
            if ($shift >= 32) {
                throw new Exception("LEB128 value too large for a u32");
            }
        }

        throw new Exception("Incomplete LEB128 sequence: termination byte not found");
    }

    public static function readFloat32(string $input, int &$offset)
    {
        $result = unpack('g', hex2bin(substr($input, $offset, 8)));
        $offset += 8;
        return $result[1];
    }

    public static function readFloat64(string $input, int &$offset)
    {
        $value = unpack('d', hex2bin(substr($input, $offset, 16)));
        $offset += 16;
        return $value[1];
    }

    public static function readBytes(string $input, int &$offset, int $count)
    {
        $bytes = hex2bin(substr($input, $offset, $count * 2));
        $offset += $count * 2;
        return $bytes;
    }
}