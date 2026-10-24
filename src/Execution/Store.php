<?php

namespace Oatmael\WasmPhp\Execution;

use Exception;
use Oatmael\WasmPhp\Type\F32;
use Oatmael\WasmPhp\Type\F64;
use Oatmael\WasmPhp\Type\Func;
use Oatmael\WasmPhp\Type\I32;
use Oatmael\WasmPhp\Type\I64;
use Oatmael\WasmPhp\Type\Code;
use Oatmael\WasmPhp\Type\Data;
use Oatmael\WasmPhp\Type\Local;
use Oatmael\WasmPhp\Type\Memory;
use Oatmael\WasmPhp\Util\ValueType;

class Store {
    protected array $import_callables;
    protected bool $initialised;

    public function __construct(
        public array $types,
        public array $codes,
        public array $functions,
        public array $memory,
        public array $data,
        public array $exports,
        public array $imports,
        public array $globals,
        public array $tables,
        public array $elements,
    )
    {
        $this->import_callables = [];
        $this->initialised = false;
    }

    public function isInitialised(): bool
    {
        return $this->initialised;
    }

    public function initialise() {
        /** @var Data $data_section */
        foreach ($this->data as $data_section) {

            /** @var Memory|null $data_memory */
            $data_memory = $this->memory[$data_section->memory_idx] ?? null;
            if (!$data_memory) {
                throw new Exception("Can't load data into memory, no memory found for index " . $data_section->memory_idx);
            }

            if ($data_section->offset + count($data_section->init) > count($data_memory->data)) {
                throw new Exception("Data section is too large to fit into memory");
            }

            array_splice($data_memory->data, $data_section->offset, count($data_section->init), $data_section->init);
        }

        $this->initialised = true;
    }

    public function setImport(string $module, string $field, callable $func): self {
        $this->import_callables[$module][$field] = $func;
        return $this;
    }

    // Function indices count imports first, then the module's own functions
    public function getFunctionType(int $function_idx): Func
    {
        $type_idx = $function_idx < count($this->imports) ?
            $this->imports[$function_idx]->idx :
            $this->functions[$function_idx - count($this->imports)] ?? throw new Exception('Invalid function index: ' . $function_idx);

        return $this->types[$type_idx];
    }

    // A block type is either empty, a single result type, or an index into the type section
    public function resolveBlockType(ValueType|int $block_type): Func
    {
        return match (true) {
            is_int($block_type)            => $this->types[$block_type] ?? throw new Exception('Invalid block type index: ' . $block_type),
            $block_type === ValueType::VOID => new Func(params: [], results: []),
            default                        => new Func(params: [], results: [$block_type]),
        };
    }

    public function pushFrame(array &$stack, array &$call_stack, int $function_idx) {
        $function = $this->getFunctionType($function_idx);

        $bottom = count($stack) - count($function->params);
        $locals = array_splice($stack, $bottom);

        if ($function_idx < count($this->imports)) {
            $import = $this->imports[$function_idx];
            $import_func = $this->import_callables[$import->module][$import->field] ?? null;
            if (!$import_func) {
                throw new Exception('Undefined import ' . $import->module . ':' . $import->field);
            }

            // TODO: provide a nicer interface for the parameters exposed to the import function (maybe some sort of reflection inference?)
            $ret = $import_func($stack, $call_stack, $this, ...$locals);
            if ($ret) {
                array_push($stack, ...(is_array($ret) ? $ret : [$ret]));
            }
            return;
        }

        /** @var Code $code */
        $code = $this->codes[$function_idx - count($this->imports)];

        /** @var Local $local */
        foreach ($code->locals as $local) {
            for ($i = 0; $i < $local->type_count; $i++) {
                $locals[] = match ($local->value_type) {
                    ValueType::I32 => new I32(0),
                    ValueType::I64 => new I64(0),
                    ValueType::F32 => new F32(0),
                    ValueType::F64 => new F64(0),
                    default => throw new Exception('Invalid local type: ' . $local->value_type->name),
                };
            }
        }

        array_push($call_stack, new Frame(
            program_counter: -1, // The frame's PC is set to -1, as the first thing a frame does during execution is to increment it
            stack_pointer: count($stack),
            instructions: $code->instructions,
            arity: count($function->results),
            locals: $locals,
        ));
    }

    public function popFrame(&$stack, &$call_stack)
    {
        /** @var Frame $frame */
        $frame = array_pop($call_stack);
        if (!$frame) {
            throw new Exception('No frame to pop');
        }

        self::unwind($stack, $frame->stack_pointer, $frame->arity);
    }

    // Branch to the label `$depth` levels out (0 is the innermost block). Branching past the outermost block returns from the function.
    public function branch(array &$stack, array &$call_stack, int $depth)
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

    // Discard the operand stack down to $height, keeping the top $arity values
    protected static function unwind(array &$stack, int $height, int $arity)
    {
        $values = $arity > 0 ? array_slice($stack, -$arity) : [];
        if (count($values) !== $arity || count($stack) - $arity < $height) {
            throw new Exception('Not enough values on the stack, expected ' . $arity);
        }

        array_splice($stack, $height);
        array_push($stack, ...$values);
    }
}
