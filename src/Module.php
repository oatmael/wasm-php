<?php

namespace Oatmael\WasmPhp;

use Exception;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Execution\Frame;
use Oatmael\WasmPhp\Type\Export;
use Oatmael\WasmPhp\Instruction\InstructionInterface;
use Oatmael\WasmPhp\Type\F32;
use Oatmael\WasmPhp\Type\F64;
use Oatmael\WasmPhp\Type\I32;
use Oatmael\WasmPhp\Type\I64;
use Oatmael\WasmPhp\Type\Memory;
use Oatmael\WasmPhp\Util\ExportType;
use Oatmael\WasmPhp\Util\ValueType;

class Module {
    protected string $magic = "\0asm";

    protected array $stack;
    protected array $call_stack;
    protected Store $store;

    public function __construct(
        protected int $version,
        protected ?int $start,
        array $types,
        array $codes,
        array $functions,
        array $memory,
        array $data,
        array $exports,
        array $imports,
        array $globals,
        array $tables,
        array $elements,
    )
    {
        $this->store = new Store(
            types:      $types,
            codes:      $codes,
            functions:  $functions,
            memory:     $memory,
            data:       $data,
            exports:    $exports,
            imports:    $imports,
            globals:    $globals,
            tables:     $tables,
            elements:   $elements,
        );
    }

    public const MAX_ITERATIONS = 1_000_000;

    public function setImport(string $module, string $field, callable $func): self {
        $this->store->setImport($module, $field, $func);
        return $this;
    }

    public function getMemory(int $index = 0): Memory
    {
        if (!isset($this->store->memory[$index])) {
            throw new Exception('Memory index out of bounds');
        }

        return $this->store->memory[$index];
    }

    public function execute(string $root, array $args)
    {
        $this->stack = [];
        $this->call_stack = [];

        if (!$this->store->isInitialised()) {
            $this->store->initialise();

            if (isset($this->start)) {
                $this->store->pushFrame($this->stack, $this->call_stack, $this->start);
                $this->executeFrame();
            }
        }

        $export = array_find($this->store->exports, static fn (Export $export) => $export->type === ExportType::FUNCTION && $export->name === $root);

        $function = $this->store->types[$this->store->functions[$export->idx - count($this->store->imports)]];
        $arity = count($function->results);

        $bad_signature = count($function->params) !== count($args);
        $expected = [];
        foreach ($function->params as $i => $param) {
            $expected_param = match ($param) {
                ValueType::I32 => I32::class,
                ValueType::I64 => I64::class,
                ValueType::F32 => F32::class,
                ValueType::F64 => F64::class,
                default => null,
            };

            if (!is_a($args[$i], $expected_param, true)) {
                $bad_signature = true;
            }
            $expected[] = $expected_param;
        }

        if ($bad_signature) {
            $provided = array_map(static fn ($arg) => get_class($arg), $args);
            throw new Exception('Bad export call - expected [' .implode(', ', $expected) . '], got [' . implode(', ', $provided) . ']');
        }

        array_push($this->stack, ...$args);

        $this->store->pushFrame($this->stack, $this->call_stack, $export->idx);
        $this->executeFrame();

        if ($arity > 0) {
            $ret = [];
            for ($i = 0; $i < $arity; $i++){
                $value = array_pop($this->stack);
                if (!$value) {
                    throw new Exception('No return value found');
                }
                $ret[] = $value;
            }

            return $ret;
        }

        return null;
    }

    protected function executeFrame()
    {
        // var_dump(end($this->call_stack));
        $iters = 0;
        while ($iters < self::MAX_ITERATIONS) {
            /** @var Frame|null $frame */
            $frame = end($this->call_stack);
            if (!$frame) {
                break;
            }

            $frame->program_counter += 1;

            /** @var InstructionInterface|null $instruction */
            $instruction = $frame->instructions[$frame->program_counter] ?? null;
            if (!$instruction) {
                break;
            }

            var_dump([
                'instruction' => $instruction::class,
                'program_counter' => $frame->program_counter,
            ]);

            $instruction->execute($this->stack, $this->call_stack, $this->store);

            $iters++;
        }
    }
}