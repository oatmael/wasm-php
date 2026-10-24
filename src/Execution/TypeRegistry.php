<?php

namespace Oatmael\WasmPhp\Execution;

use Oatmael\WasmPhp\Type\ArrayType;
use Oatmael\WasmPhp\Type\FieldType;
use Oatmael\WasmPhp\Type\Func;
use Oatmael\WasmPhp\Type\RefType;
use Oatmael\WasmPhp\Type\StructType;
use Oatmael\WasmPhp\Util\HeapType;
use Oatmael\WasmPhp\Util\PackedType;
use Oatmael\WasmPhp\Util\ValueType;

// Gives every defined type a canonical id, shared by all modules. Types are equivalent when their recursion groups
// have the same structure (iso-recursive equivalence), so equivalent types from different modules get the same id.
// https://webassembly.github.io/spec/core/valid/conventions.html#rolling-and-unrolling
class TypeRegistry
{
    // Structural description of a type => canonical id
    protected static array $ids = [];
    // Canonical id => the declared supertype's canonical id, or null
    protected static array $supertypes = [];
    // Canonical id => the composite type (Func, StructType or ArrayType)
    protected static array $definitions = [];

    // Canonical ids for a module's types, given its recursion groups as [first type index, size] pairs
    public static function canonicalise(array $types, array $rec_groups): array
    {
        $canonical = [];
        foreach ($rec_groups as [$start, $size]) {
            // References within the group are described relative to it, and references to earlier types by their canonical id
            $describe_index = static fn (int $idx) => $idx >= $start ? 'r' . ($idx - $start) : 'c' . $canonical[$idx];

            $group = [];
            for ($i = $start; $i < $start + $size; $i++) {
                $group[] = self::describe($types[$i], $describe_index);
            }

            $group = implode(';', $group);
            for ($i = $start; $i < $start + $size; $i++) {
                $id = self::$ids[$group . '#' . ($i - $start)] ??= count(self::$ids);
                $canonical[$i] = $id;
                self::$definitions[$id] ??= $types[$i];
            }

            for ($i = $start; $i < $start + $size; $i++) {
                $supertype = $types[$i]->supertypes[0] ?? null;
                self::$supertypes[$canonical[$i]] ??= $supertype === null ? null : $canonical[$supertype];
            }
        }

        return $canonical;
    }

    public static function definition(int $id): Func|StructType|ArrayType
    {
        return self::$definitions[$id];
    }

    // Whether canonical type $sub is $super, or declares it as a (transitive) supertype
    public static function isSubtype(int $sub, int $super): bool
    {
        for ($id = $sub; $id !== null; $id = self::$supertypes[$id] ?? null) {
            if ($id === $super) {
                return true;
            }
        }

        return false;
    }

    // Heap subtyping, where concrete heap types are canonical ids
    // https://webassembly.github.io/spec/core/valid/matching.html#heap-types
    public static function heapMatches(HeapType|int $sub, HeapType|int $super): bool
    {
        if ($sub === $super) {
            return true;
        }

        if (is_int($sub)) {
            return is_int($super) ?
                self::isSubtype($sub, $super) :
                self::heapMatches(self::abstractType(self::$definitions[$sub]), $super);
        }

        if (is_int($super)) {
            // Only the bottom type of a hierarchy matches a concrete type
            return $sub === self::bottomType(self::abstractType(self::$definitions[$super]));
        }

        return match ($super) {
            HeapType::ANY    => in_array($sub, [HeapType::EQ, HeapType::I31, HeapType::STRUCT, HeapType::ARRAY, HeapType::NONE], true),
            HeapType::EQ     => in_array($sub, [HeapType::I31, HeapType::STRUCT, HeapType::ARRAY, HeapType::NONE], true),
            HeapType::I31, HeapType::STRUCT, HeapType::ARRAY => $sub === HeapType::NONE,
            HeapType::FUNC   => $sub === HeapType::NOFUNC,
            HeapType::EXTERN => $sub === HeapType::NOEXTERN,
            HeapType::EXN    => $sub === HeapType::NOEXN,
            default          => false,
        };
    }

    // The abstract heap type a composite type belongs to
    public static function abstractType(Func|StructType|ArrayType $type): HeapType
    {
        return match (true) {
            $type instanceof Func       => HeapType::FUNC,
            $type instanceof StructType => HeapType::STRUCT,
            $type instanceof ArrayType  => HeapType::ARRAY,
        };
    }

    public static function bottomType(HeapType $type): HeapType
    {
        return match ($type) {
            HeapType::FUNC, HeapType::NOFUNC     => HeapType::NOFUNC,
            HeapType::EXTERN, HeapType::NOEXTERN => HeapType::NOEXTERN,
            HeapType::EXN, HeapType::NOEXN       => HeapType::NOEXN,
            default                              => HeapType::NONE,
        };
    }

    protected static function describe(Func|StructType|ArrayType $type, callable $describe_index): string
    {
        $describe_storage = static fn (ValueType|RefType|PackedType $storage) => match (true) {
            $storage instanceof RefType => ($storage->nullable ? 'n' : '') .
                ($storage->heap_type instanceof HeapType ? 'h' . $storage->heap_type->value : $describe_index($storage->heap_type)),
            default                     => 'v' . $storage->value,
        };
        $describe_field = static fn (FieldType $field) => ($field->mutable ? 'm' : '') . $describe_storage($field->storage);

        $composite = match (true) {
            $type instanceof Func       => 'func(' . implode(',', array_map($describe_storage, $type->params)) . ')(' .
                implode(',', array_map($describe_storage, $type->results)) . ')',
            $type instanceof StructType => 'struct(' . implode(',', array_map($describe_field, $type->fields)) . ')',
            $type instanceof ArrayType  => 'array(' . $describe_field($type->field) . ')',
        };

        return ($type->final ? 'final ' : '') . 'sub(' . implode(',', array_map($describe_index, $type->supertypes)) . ') ' . $composite;
    }
}
