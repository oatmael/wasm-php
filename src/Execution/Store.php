<?php

namespace Oatmael\WasmPhp\Execution;

use Exception;
use Oatmael\WasmPhp\Type\F32;
use Oatmael\WasmPhp\Type\F64;
use Oatmael\WasmPhp\Type\I32;
use Oatmael\WasmPhp\Type\I64;
use Oatmael\WasmPhp\Type\Data;
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
            $data_memory = $memory[$data_section->memory_idx] ?? null;
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

    public function pushFrame(array &$stack, array &$call_stack, int $function_idx) {
        if ($function_idx >= count($this->imports)) {
            $import = null;
            $function = $this->types[$this->functions[$function_idx - count($this->imports)]];
        } else {
            $import = $this->imports[$function_idx];
            $function = $this->types[$import->idx];
        }

        $bottom = count($stack) - count($function->params);
        $locals = array_splice($stack, $bottom);

        if ($import) {
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
                    default => throw new Exception('Invalid local type: ' . get_class($local->value_type)),
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

        if ($frame->arity > 0) {
            $ret = [];
            for ($i = 0; $i < $frame->arity; $i++){
                $value = array_pop($stack);
                if (!$value) {
                    throw new Exception('No return value found for frame, expected arity ' . $frame->arity);
                }
                $ret[] = $value;
            }

            array_splice($stack, $frame->stack_pointer);
            array_push($stack, ...$ret);
        } else {
            array_splice($stack, $frame->stack_pointer);
        }
    }
}