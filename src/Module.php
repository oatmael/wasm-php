<?php

namespace Oatmael\WasmPhp;

use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Execution\Frame;
use Oatmael\WasmPhp\Type\Export;
use Oatmael\WasmPhp\Instruction\InstructionInterface;

class Module {
    protected string $magic = "\0asm";

    protected array $stack;
    protected array $call_stack;
    protected Store $store;

    public function __construct(
        protected int $version,
        array $types,
        array $codes,
        array $functions,
        array $memory,
        array $data,
        array $exports,
        array $imports
    )
    {
        $this->store = new Store(
            $types,
            $codes,
            $functions,
            $memory,
            $data,
            $exports,
            $imports
        );
    }

    public const MAX_ITERATIONS = 1_000_000;

    public function execute(string $root, array $args)
    {
        $this->stack = [...$args];
        $this->call_stack = [];

        $export = array_find($this->store->exports, static fn (Export $export) => $export->name === $root);
        
        $this->store->pushFrame($this->stack, $this->call_stack, $export->function_idx);
        return $this->invoke($export->function_idx);
    }

    protected function invoke(int $function_idx) {
        $function = $this->store->types[$this->store->functions[$function_idx]];
        $arity = count($function->results);

        $this->executeFrame();

        if ($arity > 0) {
            $ret = array_pop($this->stack);
            return $ret;
        }

        return null;
    }

    protected function executeFrame()
    {
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

            $instruction->execute($this->stack, $this->call_stack, $this->store);

            $iters++;
        }
    }
}