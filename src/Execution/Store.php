<?php

namespace Oatmael\WasmPhp\Execution;

use Closure;
use Exception;
use Oatmael\WasmPhp\Exception\CallStackExceededException;
use Oatmael\WasmPhp\Exception\LinkException;
use Oatmael\WasmPhp\Exception\TrapException;
use Oatmael\WasmPhp\Exception\UncaughtException;
use Oatmael\WasmPhp\Import\HostFunction;
use Oatmael\WasmPhp\Module;
use Oatmael\WasmPhp\Type\ArrayRef;
use Oatmael\WasmPhp\Type\Data;
use Oatmael\WasmPhp\Type\Element;
use Oatmael\WasmPhp\Type\ExnRef;
use Oatmael\WasmPhp\Type\Export;
use Oatmael\WasmPhp\Type\ExternRef;
use Oatmael\WasmPhp\Type\Func;
use Oatmael\WasmPhp\Type\FuncRef;
use Oatmael\WasmPhp\Type\GlobalType;
use Oatmael\WasmPhp\Type\GlobalVariable;
use Oatmael\WasmPhp\Type\HostRef;
use Oatmael\WasmPhp\Type\I31Ref;
use Oatmael\WasmPhp\Type\Import;
use Oatmael\WasmPhp\Type\Memory;
use Oatmael\WasmPhp\Type\MemoryType;
use Oatmael\WasmPhp\Type\NullRef;
use Oatmael\WasmPhp\Type\RefType;
use Oatmael\WasmPhp\Type\StructRef;
use Oatmael\WasmPhp\Type\Table;
use Oatmael\WasmPhp\Type\TableType;
use Oatmael\WasmPhp\Type\Tag;
use Oatmael\WasmPhp\Type\ValueInterface;
use Oatmael\WasmPhp\Util\ExportType;
use Oatmael\WasmPhp\Util\HeapType;
use Oatmael\WasmPhp\Util\ImportType;
use Oatmael\WasmPhp\Util\SegmentMode;
use Oatmael\WasmPhp\Util\ValueType;

// A module instance: its runtime functions, tables, memories, globals, tags and segments
class Store {
    // Type index => canonical type id, see TypeRegistry
    public readonly array $canonical;

    // Index spaces, which start with the module's imports
    /** @var FuncRef[] */
    public array $funcs = [];
    /** @var Table[] */
    public array $tables = [];
    /** @var Memory[] */
    public array $memory = [];
    /** @var GlobalVariable[] */
    public array $globals = [];
    /** @var Tag[] */
    public array $tags = [];

    // Element segment index => its references, and data segment index => its bytes. Dropped segments are empty.
    public array $elements = [];
    public array $data = [];

    // Import index => the value it was resolved to
    protected array $resolved_imports = [];
    protected bool $instantiated = false;

    public function __construct(
        public readonly array $types,
        array $rec_groups,
        public readonly array $imports,
        // Type index of each function the module defines, and its code
        protected readonly array $functions,
        protected readonly array $codes,
        /** @var TableType[] */
        protected readonly array $table_types,
        /** @var MemoryType[] */
        protected readonly array $memory_types,
        /** @var GlobalType[] */
        protected readonly array $global_types,
        // Type index of each tag the module defines
        protected readonly array $tag_types,
        /** @var Export[] */
        public readonly array $exports,
        /** @var Element[] */
        protected readonly array $element_segments,
        /** @var Data[] */
        protected readonly array $data_segments,
        protected readonly ?int $start,
    )
    {
        $this->canonical = TypeRegistry::canonicalise($types, $rec_groups);
    }

    public function isInstantiated(): bool
    {
        return $this->instantiated;
    }

    // https://webassembly.github.io/spec/core/exec/modules.html#instantiation
    public function instantiate(): void
    {
        if ($this->instantiated) {
            return;
        }
        $this->instantiated = true;

        foreach ($this->imports as $i => $import) {
            $value = $this->resolved_imports[$i] ?? throw new LinkException("unknown import {$import->module}.{$import->field}");
            match ($import->type) {
                ImportType::FUNCTION => $this->funcs[] = $value,
                ImportType::TABLE    => $this->tables[] = $value,
                ImportType::MEMORY   => $this->memory[] = $value,
                ImportType::GLOBAL   => $this->globals[] = $value,
                ImportType::TAG      => $this->tags[] = $value,
            };
        }

        foreach ($this->functions as $i => $type_idx) {
            $this->funcs[] = new FuncRef($this->types[$type_idx], $this->canonical[$type_idx], $this, $this->codes[$i]);
        }

        foreach ($this->tag_types as $type_idx) {
            $this->tags[] = new Tag($this->types[$type_idx], $this->canonical[$type_idx]);
        }

        // Globals are initialised in order, as their initialisers can read earlier globals
        foreach ($this->global_types as $type) {
            $this->globals[] = new GlobalVariable($this->canonicalType($type->type), $type->mutable, $this->evaluate($type->init));
        }

        foreach ($this->memory_types as $type) {
            $this->memory[] = Memory::fromType($type);
        }

        foreach ($this->table_types as $type) {
            $init = $type->init ? $this->evaluate($type->init) : null;
            $this->tables[] = new Table($this->canonicalType($type->element_type), $type->min, $type->max, $type->is64, $init);
        }

        foreach ($this->element_segments as $segment) {
            $this->elements[] = array_map($this->evaluate(...), $segment->init);
        }

        foreach ($this->data_segments as $segment) {
            $this->data[] = $segment->init;
        }

        // Active segments are copied with table.init and memory.init, and then dropped, as are declarative segments
        foreach ($this->element_segments as $i => $segment) {
            if ($segment->mode === SegmentMode::ACTIVE) {
                $table = $this->tables[$segment->table_idx];
                $this->initTable($table, $i, $table->operand($this->evaluate($segment->offset)), 0, count($this->elements[$i]));
            }

            if ($segment->mode !== SegmentMode::PASSIVE) {
                $this->elements[$i] = [];
            }
        }

        foreach ($this->data_segments as $i => $segment) {
            if ($segment->mode === SegmentMode::ACTIVE) {
                $memory = $this->memory[$segment->memory_idx] ?? throw new Exception('Unknown memory ' . $segment->memory_idx);
                $this->initMemory($memory, $i, $memory->operand($this->evaluate($segment->offset)), 0, strlen($segment->init));
                $this->data[$i] = '';
            }
        }

        if ($this->start !== null) {
            $this->invoke($this->funcs[$this->start], []);
        }
    }

    // Resolve every import named $module.$field to $value: a PHP callable or FuncRef for functions, or a Table,
    // Memory, GlobalVariable or Tag
    public function setImport(string $module, string $field, mixed $value): void
    {
        if ($this->instantiated) {
            throw new Exception("Can't set import $module.$field after the module has been instantiated");
        }

        $found = false;
        foreach ($this->imports as $i => $import) {
            if ($import->module === $module && $import->field === $field) {
                $this->resolved_imports[$i] = $this->resolveImport($import, $value);
                $found = true;
            }
        }

        if (!$found) {
            throw new Exception("Module does not import $module.$field");
        }
    }

    public function getExport(string $name): FuncRef|Table|Memory|GlobalVariable|Tag|null
    {
        $export = array_find($this->exports, static fn (Export $export) => $export->name === $name);

        return match ($export?->type) {
            null                 => null,
            ExportType::FUNCTION => $this->funcs[$export->idx],
            ExportType::TABLE    => $this->tables[$export->idx],
            ExportType::MEMORY   => $this->memory[$export->idx],
            ExportType::GLOBAL   => $this->globals[$export->idx],
            ExportType::TAG      => $this->tags[$export->idx],
        };
    }

    // Call a function with the given arguments, returning its results
    public function invoke(FuncRef $function, array $args): array
    {
        $stack = array_values($args);
        $call_stack = [];

        $this->pushFrame($stack, $call_stack, $function);
        self::run($stack, $call_stack);

        return $stack;
    }

    // Execute instructions until the call stack is empty. No PHP recursion is used for wasm calls.
    protected static function run(array &$stack, array &$call_stack): void
    {
        while (true) {
            try {
                while ($frame = end($call_stack)) {
                    $instruction = $frame->instructions[++$frame->program_counter] ?? null;
                    if (!$instruction) {
                        return;
                    }

                    $instruction->execute($stack, $call_stack, $frame->store);
                }

                return;
            } catch (UncaughtException $exception) {
                if (!self::catch($stack, $call_stack, $exception->exception)) {
                    throw $exception;
                }
            }
        }
    }

    // Find the innermost try_table that catches $exception, unwind to it, and branch to its handler
    // https://webassembly.github.io/spec/core/exec/instructions.html#exec-throw-ref
    protected static function catch(array &$stack, array &$call_stack, ExnRef $exception): bool
    {
        for ($f = count($call_stack) - 1; $f >= 0; $f--) {
            /** @var Frame $frame */
            $frame = $call_stack[$f];
            for ($l = count($frame->control_stack) - 1; $l >= 0; $l--) {
                $label = $frame->control_stack[$l];
                foreach ($label->catches as $catch) {
                    if ($catch->tag_idx !== null && $frame->store->tags[$catch->tag_idx] !== $exception->tag) {
                        continue;
                    }

                    // Catch labels are relative to the try_table's enclosing block, so its own label is discarded too
                    array_splice($call_stack, $f + 1);
                    array_splice($frame->control_stack, $l);
                    array_splice($stack, $label->stack_height);

                    if ($catch->tag_idx !== null) {
                        array_push($stack, ...$exception->values);
                    }
                    if ($catch->ref) {
                        $stack[] = $exception;
                    }

                    $frame->store->branch($stack, $call_stack, $catch->label);
                    return true;
                }
            }
        }

        return false;
    }

    public function pushFrame(array &$stack, array &$call_stack, FuncRef $function): void
    {
        $args = array_splice($stack, count($stack) - count($function->type->params));

        if ($function->host) {
            array_push($stack, ...$function->host->call($args, $function->store));
            return;
        }

        if (count($call_stack) >= Module::MAX_CALL_STACK) {
            throw new CallStackExceededException('call stack exhausted: ' . Module::MAX_CALL_STACK . ' frames');
        }

        foreach ($function->code->locals as $local) {
            for ($i = 0; $i < $local->type_count; $i++) {
                $args[] = $local->value_type->defaultValue();
            }
        }

        $call_stack[] = new Frame(
            program_counter: -1, // The frame's PC is set to -1, as the first thing a frame does during execution is to increment it
            stack_pointer: count($stack),
            instructions: $function->code->instructions,
            arity: count($function->type->results),
            locals: $args,
            store: $function->store,
        );
    }

    public function popFrame(array &$stack, array &$call_stack): void
    {
        /** @var Frame $frame */
        $frame = array_pop($call_stack);
        if (!$frame) {
            throw new Exception('No frame to pop');
        }

        self::unwind($stack, $frame->stack_pointer, $frame->arity);
    }

    // Replace the current frame with a call to $function, for return_call and friends
    public function tailCall(array &$stack, array &$call_stack, FuncRef $function): void
    {
        $args = array_splice($stack, count($stack) - count($function->type->params));

        /** @var Frame $frame */
        $frame = array_pop($call_stack);
        array_splice($stack, $frame->stack_pointer);
        array_push($stack, ...$args);

        $this->pushFrame($stack, $call_stack, $function);
    }

    // Branch to the label `$depth` levels out (0 is the innermost block). Branching past the outermost block returns from the function.
    public function branch(array &$stack, array &$call_stack, int $depth): void
    {
        /** @var Frame $frame */
        $frame = end($call_stack);
        $label_idx = count($frame->control_stack) - 1 - $depth;
        if ($label_idx === -1) {
            $this->popFrame($stack, $call_stack);
            return;
        }

        /** @var ControlStackEntry|null $label */
        $label = $frame->control_stack[$label_idx] ?? throw new Exception('Invalid branch depth: ' . $depth);

        array_splice($frame->control_stack, $label_idx);
        self::unwind($stack, $label->stack_height, $label->arity);
        $frame->program_counter = $label->break_target;
    }

    // A block type is either empty, a single result type, or an index into the type section
    public function resolveBlockType(ValueType|RefType|int $block_type): Func
    {
        return match (true) {
            is_int($block_type)             => $this->types[$block_type] ?? throw new Exception('Invalid block type index: ' . $block_type),
            $block_type === ValueType::VOID => new Func(params: [], results: []),
            default                         => new Func(params: [], results: [$block_type]),
        };
    }

    // Evaluate a constant expression
    public function evaluate(array $expression): ValueInterface
    {
        $stack = [];
        $call_stack = [];
        foreach ($expression as $instruction) {
            $instruction->execute($stack, $call_stack, $this);
        }

        return array_pop($stack) ?? throw new Exception('Constant expression produced no value');
    }

    // table.init, also used for active element segments
    public function initTable(Table $table, int $elem_idx, int $dst, int $src, int $count): void
    {
        $elements = $this->elements[$elem_idx];
        if ($src < 0 || $count < 0 || $src > count($elements) - $count) {
            throw new TrapException('out of bounds table access');
        }

        $table->write($dst, array_slice($elements, $src, $count));
    }

    // memory.init, also used for active data segments
    public function initMemory(Memory $memory, int $data_idx, int $dst, int $src, int $count): void
    {
        $data = $this->data[$data_idx];
        if ($src < 0 || $count < 0 || $src > strlen($data) - $count) {
            throw new TrapException('out of bounds memory access');
        }

        $memory->write($dst, substr($data, $src, $count));
    }

    // Whether a value has the given type, for values passed in from PHP
    public function valueMatches(ValueInterface $value, ValueType|RefType $type): bool
    {
        return $type instanceof RefType ? $this->refMatches($value, $type) : is_a($value, $type->valueClass());
    }

    // Whether a reference has the given type, which is relative to this module
    // https://webassembly.github.io/spec/core/valid/matching.html#reference-types
    public function refMatches(ValueInterface $value, RefType $type): bool
    {
        if ($value instanceof NullRef) {
            return $type->nullable;
        }

        $heap_type = match (true) {
            $value instanceof FuncRef, $value instanceof StructRef, $value instanceof ArrayRef => $value->type_id,
            $value instanceof I31Ref    => HeapType::I31,
            $value instanceof HostRef   => HeapType::ANY,
            $value instanceof ExternRef => HeapType::EXTERN,
            $value instanceof ExnRef    => HeapType::EXN,
            default                     => null,
        };

        return $heap_type !== null && TypeRegistry::heapMatches($heap_type, $this->canonicalType($type)->heap_type);
    }

    // Replace type indices in a reference type with canonical type ids, so it can be compared across modules
    public function canonicalType(ValueType|RefType $type): ValueType|RefType
    {
        return $type instanceof RefType && is_int($type->heap_type) ?
            new RefType($type->nullable, $this->canonical[$type->heap_type]) :
            $type;
    }

    protected function resolveImport(Import $import, mixed $value): FuncRef|Table|Memory|GlobalVariable|Tag
    {
        $name = "{$import->module}.{$import->field}";
        $incompatible = static fn (string $reason) => new LinkException("incompatible import type for $name: $reason");

        switch ($import->type) {
            case ImportType::FUNCTION:
                $type_id = $this->canonical[$import->descriptor];
                if ($value instanceof FuncRef) {
                    return TypeRegistry::isSubtype($value->type_id, $type_id) ? $value : throw $incompatible('function types differ');
                }
                if (!is_callable($value)) {
                    throw $incompatible('expected a callable or FuncRef, got ' . get_debug_type($value));
                }

                $type = $this->types[$import->descriptor];
                return new FuncRef($type, $type_id, $this, host: new HostFunction(Closure::fromCallable($value), $type, $name));

            case ImportType::TABLE:
                /** @var TableType $type */
                $type = $import->descriptor;
                $matches = $value instanceof Table &&
                    $value->is64 === $type->is64 &&
                    self::limitsMatch($value->size(), $value->max, $type->min, $type->max) &&
                    self::typesEqual($value->element_type, $this->canonicalType($type->element_type));

                return $matches ? $value : throw $incompatible('expected a matching Table');

            case ImportType::MEMORY:
                /** @var MemoryType $type */
                $type = $import->descriptor;
                $matches = $value instanceof Memory &&
                    $value->is64 === $type->is64 &&
                    self::limitsMatch($value->pages(), $value->max, $type->min, $type->max);

                return $matches ? $value : throw $incompatible('expected a matching Memory');

            case ImportType::GLOBAL:
                /** @var GlobalType $type */
                $type = $import->descriptor;
                $expected = $this->canonicalType($type->type);
                $matches = $value instanceof GlobalVariable &&
                    $value->mutable === $type->mutable &&
                    ($type->mutable ? self::typesEqual($value->type, $expected) : self::typeMatches($value->type, $expected));

                return $matches ? $value : throw $incompatible('expected a matching GlobalVariable');

            case ImportType::TAG:
                $matches = $value instanceof Tag && $value->type_id === $this->canonical[$import->descriptor];

                return $matches ? $value : throw $incompatible('expected a matching Tag');
        }
    }

    // Limits match when the actual size is at least the expected minimum, and the maximum is at most the expected maximum
    protected static function limitsMatch(int $size, ?int $max, int $expected_min, ?int $expected_max): bool
    {
        return $size >= $expected_min && ($expected_max === null || ($max !== null && $max <= $expected_max));
    }

    // Value type subtyping, for canonical types
    protected static function typeMatches(ValueType|RefType $sub, ValueType|RefType $super): bool
    {
        if (!($sub instanceof RefType) || !($super instanceof RefType)) {
            return $sub === $super;
        }

        return (!$sub->nullable || $super->nullable) && TypeRegistry::heapMatches($sub->heap_type, $super->heap_type);
    }

    protected static function typesEqual(ValueType|RefType $left, ValueType|RefType $right): bool
    {
        return self::typeMatches($left, $right) && self::typeMatches($right, $left);
    }

    // Discard the operand stack down to $height, keeping the top $arity values
    protected static function unwind(array &$stack, int $height, int $arity): void
    {
        $values = $arity > 0 ? array_slice($stack, -$arity) : [];
        if (count($values) !== $arity || count($stack) - $arity < $height) {
            throw new Exception('Not enough values on the stack, expected ' . $arity);
        }

        array_splice($stack, $height);
        array_push($stack, ...$values);
    }
}
