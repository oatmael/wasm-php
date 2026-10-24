<?php

namespace Oatmael\WasmPhp\Util;

use Exception;
use Oatmael\WasmPhp\Instruction\Block;
use Oatmael\WasmPhp\Instruction\End;
use Oatmael\WasmPhp\Instruction\ExtensionOpcode;
use Oatmael\WasmPhp\Instruction\GcOpcode;
use Oatmael\WasmPhp\Instruction\IElse;
use Oatmael\WasmPhp\Instruction\IIf;
use Oatmael\WasmPhp\Instruction\InstructionInterface;
use Oatmael\WasmPhp\Instruction\Loop;
use Oatmael\WasmPhp\Instruction\RefFunc;
use Oatmael\WasmPhp\Instruction\SimdOpcode;
use Oatmael\WasmPhp\Instruction\StandardOpcode;
use Oatmael\WasmPhp\Instruction\TryTable;
use Oatmael\WasmPhp\Module;
use Oatmael\WasmPhp\Type\ArrayType;
use Oatmael\WasmPhp\Type\Code;
use Oatmael\WasmPhp\Type\Data;
use Oatmael\WasmPhp\Type\Element;
use Oatmael\WasmPhp\Type\Export;
use Oatmael\WasmPhp\Type\FieldType;
use Oatmael\WasmPhp\Type\Func;
use Oatmael\WasmPhp\Type\GlobalType;
use Oatmael\WasmPhp\Type\Import;
use Oatmael\WasmPhp\Type\Local;
use Oatmael\WasmPhp\Type\MemoryType;
use Oatmael\WasmPhp\Type\RefType;
use Oatmael\WasmPhp\Type\StructType;
use Oatmael\WasmPhp\Type\TableType;

class WasmReader {

    public const WASM_BINARY_MAGIC = "\0asm";

    // Instruction classes are named after their opcode (i32_add -> I32Add), except where that would be a reserved word
    protected const RESERVED_INSTRUCTION_NAMES = [
        'if'     => 'IIf',
        'else'   => 'IElse',
        'return' => 'IReturn',
        'throw'  => 'IThrow',
    ];

    protected int $version;
    protected array $types;
    protected array $rec_groups;
    protected array $imports;
    protected array $functions;
    protected array $tables;
    protected array $memory;
    protected array $globals;
    protected array $tags;
    protected array $exports;
    protected ?int $start;
    protected array $elements;
    protected array $codes;
    protected array $data;
    protected ?int $data_count;

    protected string $wasm;

    public function read(string $wasm): Module
    {
        $this->version = 0;
        $this->start = null;
        $this->types = [];
        $this->rec_groups = [];
        $this->imports = [];
        $this->functions = [];
        $this->tables = [];
        $this->memory = [];
        $this->globals = [];
        $this->tags = [];
        $this->exports = [];
        $this->elements = [];
        $this->codes = [];
        $this->data = [];
        $this->data_count = null;

        $this->wasm = $wasm;

        $offset = $this->readHeader();
        while ($offset < strlen($this->wasm)) {
            $this->readSection($offset);
        }

        if (count($this->functions) !== count($this->codes)) {
            throw new Exception('Function and code section have inconsistent lengths');
        }

        return new Module(
            version:    $this->version,
            types:      $this->types,
            rec_groups: $this->rec_groups,
            imports:    $this->imports,
            functions:  $this->functions,
            codes:      $this->codes,
            tables:     $this->tables,
            memory:     $this->memory,
            globals:    $this->globals,
            tags:       $this->tags,
            exports:    $this->exports,
            elements:   $this->elements,
            data:       $this->data,
            start:      $this->start,
        );
    }

    // https://webassembly.github.io/spec/core/binary/modules.html#binary-module
    protected function readHeader(): int
    {
        if (!str_starts_with($this->wasm, self::WASM_BINARY_MAGIC)) {
            throw new Exception('Invalid WASM');
        }

        $offset = strlen(self::WASM_BINARY_MAGIC);
        $version = self::readUint32($this->wasm, $offset);
        if ($version !== 1) {
            throw new Exception('Unsupported WASM version');
        }

        $this->version = $version;

        return $offset;
    }

    // https://webassembly.github.io/spec/core/binary/modules.html#sections
    protected function readSection(int &$offset): void
    {
        $section_id = self::readUint8($this->wasm, $offset);
        $section = Section::tryFrom($section_id) ?? throw new Exception('Invalid section id: ' . $section_id);
        $section_size = self::readLEB128Uint32($this->wasm, $offset);
        $section_end = $offset + $section_size;

        match ($section) {
            // https://webassembly.github.io/spec/core/binary/modules.html#binary-customsec
            Section::CUSTOM     => $offset = $section_end,
            Section::TYPE       => $this->readVec($offset, $this->readRecType(...)),
            Section::IMPORT     => $this->imports = $this->readVec($offset, $this->readImport(...)),
            Section::FUNCTION   => $this->functions = $this->readVec($offset, fn (int &$offset) => self::readLEB128Uint32($this->wasm, $offset)),
            Section::TABLE      => $this->tables = $this->readVec($offset, $this->readTable(...)),
            Section::MEMORY     => $this->memory = $this->readVec($offset, $this->readMemoryType(...)),
            Section::GLOBAL     => $this->globals = $this->readVec($offset, $this->readGlobal(...)),
            Section::EXPORT     => $this->exports = $this->readVec($offset, $this->readExport(...)),
            Section::START      => $this->start = self::readLEB128Uint32($this->wasm, $offset),
            Section::ELEMENT    => $this->elements = $this->readVec($offset, $this->readElement(...)),
            Section::CODE       => $this->codes = $this->readVec($offset, $this->readCode(...)),
            Section::DATA       => $this->data = $this->readVec($offset, $this->readData(...)),
            Section::DATA_COUNT => $this->data_count = self::readLEB128Uint32($this->wasm, $offset),
            Section::TAG        => $this->tags = $this->readVec($offset, $this->readTag(...)),
        };

        if ($offset !== $section_end) {
            throw new Exception("Malformed {$section->name} section - expected to end at byte $section_end, ended at byte $offset");
        }
    }

    // https://webassembly.github.io/spec/core/binary/conventions.html#vectors
    protected function readVec(int &$offset, callable $read_item): array
    {
        $count = self::readLEB128Uint32($this->wasm, $offset);
        $items = [];
        for ($i = 0; $i < $count; $i++) {
            $items[] = $read_item($offset);
        }

        return $items;
    }

    // https://webassembly.github.io/spec/core/binary/types.html#limits
    protected function readLimits(int &$offset): array
    {
        $flags = self::readUint8($this->wasm, $offset);
        if ($flags & ~0x05) {
            throw new Exception('Unsupported limits flags: ' . $flags . ' (shared memories and custom page sizes are not supported)');
        }

        $is64 = (bool) ($flags & 0x04);
        $min = $is64 ? self::readLEB128Uint64($this->wasm, $offset) : self::readLEB128Uint32($this->wasm, $offset);
        $max = !($flags & 0x01) ? null : ($is64 ? self::readLEB128Uint64($this->wasm, $offset) : self::readLEB128Uint32($this->wasm, $offset));

        // u64 limits above 2^63 wrap negative, and are too large to allocate anyway
        return [$min < 0 ? PHP_INT_MAX : $min, $max !== null && $max < 0 ? PHP_INT_MAX : $max, $is64];
    }

    // A constant expression, as its instructions without the final end
    // https://webassembly.github.io/spec/core/valid/instructions.html#constant-expressions
    protected function readConstExpr(int &$offset): array
    {
        $instructions = [];
        while (!(($instruction = self::readInstruction($this->wasm, $offset)) instanceof End)) {
            $instructions[] = $instruction;
        }

        return $instructions;
    }

    // A recursion group of one or more types. Types outside an explicit rec group are in a group of their own.
    // https://webassembly.github.io/spec/core/binary/types.html#recursive-types
    protected function readRecType(int &$offset): void
    {
        $start = count($this->types);
        if (ord($this->wasm[$offset] ?? "\0") === 0x4E) {
            $offset++;
            $this->readVec($offset, function (int &$offset) {
                $this->types[] = $this->readSubType($offset);
            });
        } else {
            $this->types[] = $this->readSubType($offset);
        }

        $this->rec_groups[] = [$start, count($this->types) - $start];
    }

    // https://webassembly.github.io/spec/core/binary/types.html#binary-subtype
    protected function readSubType(int &$offset): Func|StructType|ArrayType
    {
        $form = self::readUint8($this->wasm, $offset);
        if ($form !== 0x50 && $form !== 0x4F) {
            return $this->readCompositeType($form, $offset, [], true);
        }

        $supertypes = $this->readVec($offset, fn (int &$offset) => self::readLEB128Uint32($this->wasm, $offset));
        return $this->readCompositeType(self::readUint8($this->wasm, $offset), $offset, $supertypes, $form === 0x4F);
    }

    // https://webassembly.github.io/spec/core/binary/types.html#composite-types
    protected function readCompositeType(int $form, int &$offset, array $supertypes, bool $final): Func|StructType|ArrayType
    {
        $read_value_type = fn (int &$offset) => self::readValueType($this->wasm, $offset);
        $read_field = function (int &$offset) {
            $storage = self::readStorageType($this->wasm, $offset);
            return new FieldType($storage, (bool) self::readUint8($this->wasm, $offset));
        };

        return match ($form) {
            0x60    => new Func($this->readVec($offset, $read_value_type), $this->readVec($offset, $read_value_type), $supertypes, $final),
            0x5F    => new StructType($this->readVec($offset, $read_field), $supertypes, $final),
            0x5E    => new ArrayType($read_field($offset), $supertypes, $final),
            default => throw new Exception(sprintf('Invalid composite type form 0x%02x', $form)),
        };
    }

    // https://webassembly.github.io/spec/core/binary/modules.html#import-section
    protected function readImport(int &$offset): Import
    {
        $module = self::readName($this->wasm, $offset);
        $field = self::readName($this->wasm, $offset);
        $import_type = ImportType::tryFrom(self::readUint8($this->wasm, $offset)) ?? throw new Exception("Invalid import kind for $module.$field");

        $descriptor = match ($import_type) {
            ImportType::FUNCTION => self::readLEB128Uint32($this->wasm, $offset),
            ImportType::TABLE    => $this->readTableType($offset),
            ImportType::MEMORY   => $this->readMemoryType($offset),
            ImportType::GLOBAL   => $this->readGlobalType($offset),
            ImportType::TAG      => $this->readTag($offset),
        };

        return new Import($module, $field, $import_type, $descriptor);
    }

    // https://webassembly.github.io/spec/core/binary/modules.html#table-section
    protected function readTable(int &$offset): TableType
    {
        // Tables with an initialiser expression are prefixed with 0x40 0x00
        if (ord($this->wasm[$offset] ?? "\0") !== 0x40) {
            return $this->readTableType($offset);
        }

        $offset += 2;
        $type = $this->readTableType($offset);
        return new TableType($type->element_type, $type->min, $type->max, $type->is64, $this->readConstExpr($offset));
    }

    protected function readTableType(int &$offset): TableType
    {
        $element_type = self::readValueType($this->wasm, $offset);
        if (!($element_type instanceof RefType)) {
            throw new Exception('Table element type must be a reference type');
        }

        [$min, $max, $is64] = $this->readLimits($offset);
        return new TableType($element_type, $min, $max, $is64);
    }

    // https://webassembly.github.io/spec/core/binary/types.html#memory-types
    protected function readMemoryType(int &$offset): MemoryType
    {
        [$min, $max, $is64] = $this->readLimits($offset);
        return new MemoryType($min, $max, $is64);
    }

    // https://webassembly.github.io/spec/core/binary/modules.html#global-section
    protected function readGlobal(int &$offset): GlobalType
    {
        $type = $this->readGlobalType($offset);
        return new GlobalType($type->type, $type->mutable, $this->readConstExpr($offset));
    }

    protected function readGlobalType(int &$offset): GlobalType
    {
        $value_type = self::readValueType($this->wasm, $offset);
        $mutable = match (self::readUint8($this->wasm, $offset)) {
            0x00    => false,
            0x01    => true,
            default => throw new Exception('Invalid global mutability'),
        };

        return new GlobalType($value_type, $mutable);
    }

    // A tag's attribute (always 0, for exceptions) and type index
    // https://webassembly.github.io/spec/core/binary/modules.html#tag-section
    protected function readTag(int &$offset): int
    {
        if (self::readUint8($this->wasm, $offset) !== 0x00) {
            throw new Exception('Invalid tag attribute');
        }

        return self::readLEB128Uint32($this->wasm, $offset);
    }

    // https://webassembly.github.io/spec/core/binary/modules.html#export-section
    protected function readExport(int &$offset): Export
    {
        $name = self::readName($this->wasm, $offset);
        $export_type = ExportType::tryFrom(self::readUint8($this->wasm, $offset)) ?? throw new Exception("Invalid export kind for $name");
        $idx = self::readLEB128Uint32($this->wasm, $offset);

        return new Export($name, $export_type, $idx);
    }

    // Element segments come in 8 forms. The flags' bits mean: 1 - passive or declarative, 2 - an explicit table
    // index (or declarative, if passive), 4 - elements are expressions instead of function indices.
    // https://webassembly.github.io/spec/core/binary/modules.html#element-section
    protected function readElement(int &$offset): Element
    {
        $flags = self::readLEB128Uint32($this->wasm, $offset);
        if ($flags > 7) {
            throw new Exception('Invalid element segment flags: ' . $flags);
        }

        $mode = match (true) {
            !($flags & 0x01) => SegmentMode::ACTIVE,
            !($flags & 0x02) => SegmentMode::PASSIVE,
            default          => SegmentMode::DECLARATIVE,
        };

        $table_idx = $flags === 0x02 || $flags === 0x06 ? self::readLEB128Uint32($this->wasm, $offset) : 0;
        $table_offset = $mode === SegmentMode::ACTIVE ? $this->readConstExpr($offset) : [];

        // Forms 0 and 4 have an implicit funcref type, while the others give an element kind or reference type
        $type = new RefType(true, HeapType::FUNC);
        if ($flags & 0x03) {
            if ($flags & 0x04) {
                $type = self::readValueType($this->wasm, $offset);
            } elseif (self::readUint8($this->wasm, $offset) !== 0x00) {
                throw new Exception('Invalid element kind');
            }
        }

        $init = $flags & 0x04 ?
            $this->readVec($offset, $this->readConstExpr(...)) :
            $this->readVec($offset, fn (int &$offset) => [new RefFunc(self::readLEB128Uint32($this->wasm, $offset))]);

        return new Element($mode, $type, $init, $table_idx, $table_offset);
    }

    // https://webassembly.github.io/spec/core/binary/modules.html#code-section
    protected function readCode(int &$offset): Code
    {
        $body_size = self::readLEB128Uint32($this->wasm, $offset);
        $body_end = $offset + $body_size;

        $locals = $this->readVec($offset, function (int &$offset) {
            $type_count = self::readLEB128Uint32($this->wasm, $offset);
            return new Local($type_count, self::readValueType($this->wasm, $offset));
        });

        $instructions = [];
        while ($offset < $body_end) {
            $instructions[] = self::readInstruction($this->wasm, $offset);
        }

        if ($offset !== $body_end) {
            throw new Exception('Malformed code body - instructions overran the body size');
        }

        self::linkBlocks($instructions);

        return new Code($locals, $instructions);
    }

    // Record the else/end indices on each structured instruction, so they don't need to be searched for at runtime
    protected static function linkBlocks(array $instructions): void
    {
        $open = [];
        foreach ($instructions as $i => $instruction) {
            if ($instruction instanceof Block || $instruction instanceof Loop || $instruction instanceof IIf || $instruction instanceof TryTable) {
                $open[] = $instruction;
            } elseif ($instruction instanceof IElse) {
                $if = end($open);
                if (!($if instanceof IIf) || $if->else !== null) {
                    throw new Exception('Else without a matching if at instruction ' . $i);
                }
                $if->else = $i;
            } elseif ($instruction instanceof End) {
                // An end with no open block closes the function body
                $block = array_pop($open);
                if ($block && !($block instanceof Loop)) {
                    $block->end = $i;
                }
            }
        }

        if ($open) {
            throw new Exception('Malformed code body - unclosed block');
        }
    }

    // https://webassembly.github.io/spec/core/binary/modules.html#data-section
    protected function readData(int &$offset): Data
    {
        $flags = self::readLEB128Uint32($this->wasm, $offset);
        if ($flags > 2) {
            throw new Exception('Invalid data segment flags: ' . $flags);
        }

        $memory_idx = $flags === 0x02 ? self::readLEB128Uint32($this->wasm, $offset) : 0;
        $memory_offset = $flags === 0x01 ? [] : $this->readConstExpr($offset);
        $bytes = self::readBytes($this->wasm, $offset, self::readLEB128Uint32($this->wasm, $offset));

        return new Data($flags === 0x01 ? SegmentMode::PASSIVE : SegmentMode::ACTIVE, $bytes, $memory_idx, $memory_offset);
    }

    // https://webassembly.github.io/spec/core/binary/instructions.html
    public static function readInstruction(string $input, int &$offset): InstructionInterface
    {
        $byte = self::readUint8($input, $offset);
        [$opcode, $namespace] = match ($byte) {
            GcOpcode::PREFIX        => [GcOpcode::tryFrom(self::readLEB128Uint32($input, $offset)), 'Gc\\'],
            ExtensionOpcode::PREFIX => [ExtensionOpcode::tryFrom(self::readLEB128Uint32($input, $offset)), 'Extension\\'],
            SimdOpcode::PREFIX      => [SimdOpcode::tryFrom(self::readLEB128Uint32($input, $offset)), 'Simd\\'],
            default                 => [StandardOpcode::tryFrom($byte), ''],
        };

        if (!$opcode) {
            throw new Exception(sprintf('Unknown opcode 0x%02x', $byte));
        }

        $class = 'Oatmael\\WasmPhp\\Instruction\\' . $namespace .
            (self::RESERVED_INSTRUCTION_NAMES[$opcode->name] ?? str_replace('_', '', ucwords($opcode->name, '_')));
        if (!class_exists($class)) {
            throw new Exception('No implementation for opcode ' . $opcode->name);
        }

        return $class::fromInput($input, $offset);
    }

    // A block type is empty (0x40), a single value type, or a type index encoded as a positive s33
    // https://webassembly.github.io/spec/core/binary/instructions.html#control-instructions
    public static function readBlockType(string $input, int &$offset): ValueType|RefType|int
    {
        $byte = ord($input[$offset] ?? "\0");
        if ($byte === ValueType::VOID->value) {
            $offset++;
            return ValueType::VOID;
        }

        if ($byte >= 0x40 && $byte < 0x80) {
            return self::readValueType($input, $offset);
        }

        return self::readSignedLEB128($input, $offset, 33);
    }

    // https://webassembly.github.io/spec/core/binary/types.html#value-types
    public static function readValueType(string $input, int &$offset): ValueType|RefType
    {
        $byte = self::readUint8($input, $offset);
        return match (true) {
            $byte === 0x63, $byte === 0x64       => new RefType($byte === 0x63, self::readHeapType($input, $offset)),
            HeapType::tryFrom($byte) !== null     => new RefType(true, HeapType::from($byte)),
            $byte !== ValueType::VOID->value && ValueType::tryFrom($byte) !== null => ValueType::from($byte),
            default                              => throw new Exception(sprintf('Invalid value type 0x%02x', $byte)),
        };
    }

    // An abstract heap type, or a type index encoded as a positive s33
    // https://webassembly.github.io/spec/core/binary/types.html#heap-types
    public static function readHeapType(string $input, int &$offset): HeapType|int
    {
        $heap_type = self::readSignedLEB128($input, $offset, 33);
        if ($heap_type >= 0) {
            return $heap_type;
        }

        return HeapType::tryFrom($heap_type & 0x7F) ?? throw new Exception('Invalid heap type: ' . $heap_type);
    }

    // https://webassembly.github.io/spec/core/binary/types.html#binary-storagetype
    public static function readStorageType(string $input, int &$offset): ValueType|RefType|PackedType
    {
        $packed = PackedType::tryFrom(ord($input[$offset] ?? "\0"));
        if ($packed) {
            $offset++;
            return $packed;
        }

        return self::readValueType($input, $offset);
    }

    // A memory instruction's immediates: [align, memory index, offset]. Bit 6 of the alignment flags an explicit
    // memory index (multi-memory), and the offset is a u64 (memory64), which wraps negative above 2^63.
    // https://webassembly.github.io/spec/core/binary/instructions.html#memory-instructions
    public static function readMemArg(string $input, int &$offset): array
    {
        $align = self::readLEB128Uint32($input, $offset);
        $memory_idx = 0;
        if ($align & 0x40) {
            $align &= ~0x40;
            $memory_idx = self::readLEB128Uint32($input, $offset);
        }

        return [$align, $memory_idx, self::readLEB128Uint64($input, $offset)];
    }

    public static function readName(string $input, int &$offset): string
    {
        $size = self::readLEB128Uint32($input, $offset);
        $name = self::readBytes($input, $offset, $size);
        if (!mb_check_encoding($name, 'UTF-8')) {
            throw new Exception('Invalid UTF-8 encoded string');
        }

        return $name;
    }

    public static function readUint8(string $input, int &$offset): int
    {
        if (!isset($input[$offset])) {
            throw new Exception('Unexpected end of input at byte ' . $offset);
        }

        return ord($input[$offset++]);
    }

    public static function readUint32(string $input, int &$offset): int
    {
        return self::readFixed($input, $offset, 'V', 4);
    }

    // https://webassembly.github.io/spec/core/binary/values.html#integers
    public static function readLEB128Uint32(string $input, int &$offset): int
    {
        $result = 0;
        $shift = 0;
        do {
            if ($shift >= 35) {
                throw new Exception('LEB128 value too large for a u32');
            }

            $byte = self::readUint8($input, $offset);
            $result |= ($byte & 0x7F) << $shift;
            $shift += 7;
        } while ($byte & 0x80);

        if ($result > 0xFFFFFFFF) {
            throw new Exception('LEB128 value too large for a u32');
        }

        return $result;
    }

    // Values above 2^63 don't fit in a PHP int, so they wrap negative
    public static function readLEB128Uint64(string $input, int &$offset): int
    {
        $result = 0;
        $shift = 0;
        do {
            if ($shift >= 70) {
                throw new Exception('LEB128 value too large for a u64');
            }

            $byte = self::readUint8($input, $offset);
            if ($shift === 63 && ($byte & 0x7E)) {
                throw new Exception('LEB128 value too large for a u64');
            }

            $result |= ($byte & 0x7F) << $shift;
            $shift += 7;
        } while ($byte & 0x80);

        return $result;
    }

    public static function readLEB128Int32(string $input, int &$offset): int
    {
        return self::readSignedLEB128($input, $offset, 32);
    }

    public static function readLEB128Int64(string $input, int &$offset): int
    {
        return self::readSignedLEB128($input, $offset, 64);
    }

    public static function readFloat32(string $input, int &$offset): float
    {
        return self::readFixed($input, $offset, 'g', 4);
    }

    public static function readFloat64(string $input, int &$offset): float
    {
        return self::readFixed($input, $offset, 'e', 8);
    }

    public static function readBytes(string $input, int &$offset, int $count): string
    {
        if ($offset + $count > strlen($input)) {
            throw new Exception('Unexpected end of input at byte ' . $offset);
        }

        $bytes = substr($input, $offset, $count);
        $offset += $count;
        return $bytes;
    }

    protected static function readSignedLEB128(string $input, int &$offset, int $bits): int
    {
        $max_bytes = intdiv($bits + 6, 7);
        $result = 0;
        $shift = 0;
        do {
            if ($shift >= $max_bytes * 7) {
                throw new Exception("LEB128 value too large for an s$bits");
            }

            $byte = self::readUint8($input, $offset);
            $result |= ($byte & 0x7F) << $shift;
            $shift += 7;
        } while ($byte & 0x80);

        // Sign extend from the final byte's sign bit
        if ($shift < 64 && ($byte & 0x40)) {
            $result |= -1 << $shift;
        }

        return $result;
    }

    protected static function readFixed(string $input, int &$offset, string $format, int $size): int|float
    {
        return unpack($format, self::readBytes($input, $offset, $size))[1];
    }
}
