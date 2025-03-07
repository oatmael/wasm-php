<?php

namespace Oatmael\WasmPhp\Execution;

use Exception;
use Oatmael\WasmPhp\Type\F32;
use Oatmael\WasmPhp\Type\F64;
use Oatmael\WasmPhp\Type\I32;
use Oatmael\WasmPhp\Type\I64;
use Oatmael\WasmPhp\Type\Import;

class Store {
    protected array $import_callables;

    public function __construct(
        public array $types,
        public array $codes,
        public array $functions,
        public array $memory,
        public array $data,
        public array $exports,
        public array $imports
    )
    {
        $this->import_callables = [];
    }

    public function setImport(string $module, string $field, callable $func): self {
        $this->import_callables[$module][$field] = $func;
        return $this;
    }

    public function pushFrame(array &$stack, array &$call_stack, int $function_idx) {

        if ($function_idx >= count($this->imports)) {
            $function = $this->types[$this->functions[$function_idx - count($this->imports)]];
        } else {
            $function = $this->types[$function_idx];
        }

        $bottom = count($stack) - count($function->params);
        $locals = array_splice($stack, $bottom);

        if ($import = array_find($this->imports, static fn (Import $import) => $import->function_idx === $function_idx)) {
            $import_func = $this->import_callables[$import->module][$import->field] ?? null;
            if (!$import_func) {
                throw new Exception('Undefined import ' . $import->module . ':' . $import->field);
            }

            $ret = $import_func($this, ...$locals);
            if ($ret) {
                array_push($stack, ...(is_array($ret) ? $ret : [$ret]));
            }
            return;
        }

        /** @var Code $code */
        $code = $this->codes[$function_idx- count($this->imports)];

        /** @var Local $local */
        foreach ($code->locals as $local) {
            for ($i = 0; $i < $local->type_count; $i++) {
                $locals[] = match (get_class($local->value_type)) {
                    I32::class => new I32(0),
                    I64::class => new I64(0),
                    F32::class => new F32(0),
                    F64::class => new F64(0),
                };
            }
        }

        array_push($call_stack, new Frame(
            program_counter: -1,
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
            throw new Exception('No frame found for end opcode');
        }

        if ($frame->arity > 0) {
            $ret = [];
            for ($i = 0; $i < $frame->arity; $i++){
                $value = array_pop($stack);
                if (!$value) {
                    throw new Exception('No return value found for end opcode');
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