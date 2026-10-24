<?php

namespace Oatmael\WasmPhp\Util;

use Exception;
use Oatmael\WasmPhp\Instruction\Block;
use Oatmael\WasmPhp\Instruction\End;
use Oatmael\WasmPhp\Instruction\ExtensionOpcode;
use Oatmael\WasmPhp\Instruction\F32Const;
use Oatmael\WasmPhp\Instruction\F64Const;
use Oatmael\WasmPhp\Instruction\I32Const;
use Oatmael\WasmPhp\Instruction\I64Const;
use Oatmael\WasmPhp\Instruction\IElse;
use Oatmael\WasmPhp\Instruction\IIf;
use Oatmael\WasmPhp\Instruction\InstructionInterface;
use Oatmael\WasmPhp\Instruction\Loop;
use Oatmael\WasmPhp\Instruction\SimdOpcode;
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
use Oatmael\WasmPhp\Type\ValueInterface;

class WasmReader {

    public const WASM_BINARY_MAGIC = "\0asm";

    // Instruction classes are named after their opcode (i32_add -> I32Add), except where that would be a reserved word
    protected const RESERVED_INSTRUCTION_NAMES = [
        'if'     => 'IIf',
        'else'   => 'IElse',
        'return' => 'IReturn',
    ];

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

    public function read(string $wasm): Module
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
        $this->data_count = null;

        $this->wasm = $wasm;

        $offset = $this->readHeader();
        while ($offset < strlen($this->wasm)) {
            $this->readSection($offset);
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
            Section::TYPE       => $this->types = $this->readVec($offset, $this->readFuncType(...)),
            Section::IMPORT     => $this->imports = $this->readVec($offset, $this->readImport(...)),
            Section::FUNCTION   => $this->functions = $this->readVec($offset, fn (int &$offset) => self::readLEB128Uint32($this->wasm, $offset)),
            Section::TABLE      => $this->tables = $this->readVec($offset, $this->readTable(...)),
            Section::MEMORY     => $this->memory = $this->readVec($offset, $this->readMemory(...)),
            Section::GLOBAL     => $this->globals = $this->readVec($offset, $this->readGlobal(...)),
            Section::EXPORT     => $this->exports = $this->readVec($offset, $this->readExport(...)),
            Section::START      => $this->start = self::readLEB128Uint32($this->wasm, $offset),
            Section::ELEMENT    => $this->elements = $this->readVec($offset, $this->readElement(...)),
            Section::CODE       => $this->codes = $this->readVec($offset, $this->readCode(...)),
            Section::DATA       => $this->data = $this->readVec($offset, $this->readData(...)),
            Section::DATA_COUNT => $this->data_count = self::readLEB128Uint32($this->wasm, $offset),
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

    protected function readValueType(int &$offset): ValueType
    {
        $value_type = self::readUint8($this->wasm, $offset);
        return ValueType::tryFrom($value_type) ?? throw new Exception('Invalid value type: ' . $value_type);
    }

    // https://webassembly.github.io/spec/core/binary/types.html#limits
    protected function readLimits(int &$offset): array
    {
        $flags = self::readUint8($this->wasm, $offset);
        $min = self::readLEB128Uint32($this->wasm, $offset);
        $max = match ($flags) {
            0x00    => null,
            0x01    => self::readLEB128Uint32($this->wasm, $offset),
            default => throw new Exception('Unsupported limits flags: ' . $flags),
        };

        return [$min, $max];
    }

    // Constant expressions are a single const instruction followed by end
    // https://webassembly.github.io/spec/core/valid/instructions.html#constant-expressions
    protected function readConstExpr(int &$offset): ValueInterface
    {
        $instruction = self::readInstruction($this->wasm, $offset);
        $value = match (true) {
            $instruction instanceof I32Const => $instruction->const,
            $instruction instanceof I64Const => new I64($instruction->value),
            $instruction instanceof F32Const => new F32($instruction->value),
            $instruction instanceof F64Const => new F64($instruction->value),
            default => throw new Exception('Unsupported constant expression: ' . $instruction::class),
        };

        if (!(self::readInstruction($this->wasm, $offset) instanceof End)) {
            throw new Exception('Constant expression must contain a single instruction');
        }

        return $value;
    }

    // Segment offsets are an i32 constant expression, interpreted as unsigned
    protected function readOffsetExpr(int &$offset): int
    {
        $value = $this->readConstExpr($offset);
        if (!($value instanceof I32)) {
            throw new Exception('Segment offset must be an i32 constant expression');
        }

        return $value->toUnsigned()->value;
    }

    // https://webassembly.github.io/spec/core/binary/types.html#function-types
    protected function readFuncType(int &$offset): Func
    {
        // WASM v1 only has one type
        Type::from(self::readUint8($this->wasm, $offset));

        $params = $this->readVec($offset, $this->readValueType(...));
        $results = $this->readVec($offset, $this->readValueType(...));

        return new Func($params, $results);
    }

    // https://webassembly.github.io/spec/core/binary/modules.html#import-section
    protected function readImport(int &$offset): Import
    {
        $module = self::readName($this->wasm, $offset);
        $field = self::readName($this->wasm, $offset);
        $import_type = ImportType::from(self::readUint8($this->wasm, $offset));
        if ($import_type !== ImportType::FUNCTION) {
            throw new Exception("Unsupported import $module.$field - only function imports are supported, got {$import_type->name}");
        }

        $idx = self::readLEB128Uint32($this->wasm, $offset);

        return new Import($module, $field, $import_type, $idx);
    }

    // https://webassembly.github.io/spec/core/binary/modules.html#table-section
    protected function readTable(int &$offset): Table
    {
        $element_type = $this->readValueType($offset);
        [$min, $max] = $this->readLimits($offset);

        return new Table($element_type, $min, $max);
    }

    // https://webassembly.github.io/spec/core/binary/modules.html#memory-section
    protected function readMemory(int &$offset): Memory
    {
        [$min, $max] = $this->readLimits($offset);

        return new Memory($min, $max);
    }

    // https://webassembly.github.io/spec/core/binary/modules.html#global-section
    protected function readGlobal(int &$offset): GlobalImmutable|GlobalMutable
    {
        $value_type = $this->readValueType($offset);
        $mutability = GlobalMutability::from(self::readUint8($this->wasm, $offset));
        $value = $this->readConstExpr($offset);

        return $mutability === GlobalMutability::Immutable ?
            new GlobalImmutable($value_type, $value) :
            new GlobalMutable($value_type, $value);
    }

    // https://webassembly.github.io/spec/core/binary/modules.html#export-section
    protected function readExport(int &$offset): Export
    {
        $name = self::readName($this->wasm, $offset);
        $export_type = ExportType::from(self::readUint8($this->wasm, $offset));
        $idx = self::readLEB128Uint32($this->wasm, $offset);

        return new Export($name, $export_type, $idx);
    }

    // https://webassembly.github.io/spec/core/binary/modules.html#element-section
    protected function readElement(int &$offset): Element
    {
        $flags = self::readLEB128Uint32($this->wasm, $offset);
        if ($flags !== 0) {
            throw new Exception('Unsupported element segment flags: ' . $flags);
        }

        $table_offset = $this->readOffsetExpr($offset);
        $function_indices = $this->readVec($offset, fn (int &$offset) => self::readLEB128Uint32($this->wasm, $offset));

        return new Element(0, $table_offset, $function_indices);
    }

    // https://webassembly.github.io/spec/core/binary/modules.html#code-section
    protected function readCode(int &$offset): Code
    {
        $body_size = self::readLEB128Uint32($this->wasm, $offset);
        $body_end = $offset + $body_size;

        $locals = $this->readVec($offset, function (int &$offset) {
            $type_count = self::readLEB128Uint32($this->wasm, $offset);
            return new Local($type_count, $this->readValueType($offset));
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
            if ($instruction instanceof Block || $instruction instanceof Loop || $instruction instanceof IIf) {
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
                if ($block instanceof Block || $block instanceof IIf) {
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
        $memory_idx = match ($flags) {
            0x00    => 0,
            0x02    => self::readLEB128Uint32($this->wasm, $offset),
            default => throw new Exception('Unsupported data segment flags: ' . $flags),
        };

        $memory_offset = $this->readOffsetExpr($offset);
        $data_size = self::readLEB128Uint32($this->wasm, $offset);
        $bytes = self::readBytes($this->wasm, $offset, $data_size);

        return new Data(
            $memory_idx,
            $memory_offset,
            init: array_values(unpack('C*', $bytes)),
        );
    }

    // https://webassembly.github.io/spec/core/binary/instructions.html
    public static function readInstruction(string $input, int &$offset): InstructionInterface
    {
        $byte = self::readUint8($input, $offset);
        [$opcode, $namespace] = match ($byte) {
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

    // https://webassembly.github.io/spec/core/binary/instructions.html#control-instructions
    public static function readBlockType(string $input, int &$offset): ValueType|int
    {
        // Encoded as an s33: non-negative values are type indices, negative values are a single byte value type (or empty)
        $block_type = self::readSignedLEB128($input, $offset, 33);
        if ($block_type >= 0) {
            return $block_type;
        }

        return ValueType::tryFrom($block_type & 0x7F) ?? throw new Exception('Invalid block type: ' . $block_type);
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
