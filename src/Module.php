<?php

namespace Oatmael\WasmPhp;

use Exception;
use Oatmael\WasmPhp\Exception\CallStackExceededException;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Execution\Frame;
use Oatmael\WasmPhp\Type\Export;
use Oatmael\WasmPhp\Instruction\InstructionInterface;
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

    public const MAX_CALL_STACK = 10_000;

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

        $export = array_find($this->store->exports, static fn (Export $export) => $export->type === ExportType::FUNCTION && $export->name === $root)
            ?? throw new Exception("No exported function named '$root'");

        $function = $this->store->getFunctionType($export->idx);
        $arity = count($function->results);

        $expected = array_map(static fn (ValueType $param) => $param->valueClass(), $function->params);

        $args = array_values($args);
        $bad_signature = count($expected) !== count($args) ||
            array_any($args, static fn ($arg, int $i) => !is_a($arg, $expected[$i]));

        if ($bad_signature) {
            $provided = array_map(get_debug_type(...), $args);
            throw new Exception('Bad export call - expected [' .implode(', ', $expected) . '], got [' . implode(', ', $provided) . ']');
        }

        array_push($this->stack, ...$args);

        $this->store->pushFrame($this->stack, $this->call_stack, $export->idx);
        $this->executeFrame();

        if ($arity > 0) {
            if (count($this->stack) < $arity) {
                throw new Exception('No return value found');
            }

            return array_slice($this->stack, -$arity);
        }

        return null;
    }

    protected function executeFrame()
    {
        $iters = 0;
        while (true) {
            /** @var Frame|null $frame */
            $frame = end($this->call_stack);
            if (!$frame) {
                break;
            }

            if (count($this->call_stack) > self::MAX_CALL_STACK) {
                throw new CallStackExceededException('Call stack size of ' . self::MAX_CALL_STACK . ' exceeded');
            }

            $frame->program_counter += 1;

            /** @var InstructionInterface|null $instruction */
            $instruction = $frame->instructions[$frame->program_counter] ?? null;
            if (!$instruction) {
                break;
            }

            $instruction->execute($this->stack, $this->call_stack, $this->store);

            $iters++;
        }
    }
}