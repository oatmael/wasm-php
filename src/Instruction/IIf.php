<?php

namespace Oatmael\WasmPhp\Instruction;

use Oatmael\WasmPhp\Execution\ControlStackEntry;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Type\RefType;
use Oatmael\WasmPhp\Util\ValueType;
use Oatmael\WasmPhp\Util\WasmReader;

class IIf implements InstructionInterface {
    // Indices of the matching else (if any) and end instructions, linked by WasmReader once the function body is decoded
    public ?int $else = null;
    public int $end;

    public function __construct(
        public readonly ValueType|RefType|int $block_type,
    ) {}

    public static function fromInput(string $input, int &$offset): InstructionInterface {
        $block_type = WasmReader::readBlockType($input, $offset);
        return new self($block_type);
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        /** @var Frame $frame */
        $frame = end($call_stack);

        $condition = array_pop($stack);
        if (!$condition->getValue()) {
            if ($this->else === null) {
                $frame->program_counter = $this->end;
                return;
            }

            $frame->program_counter = $this->else;
        }

        $block_type = $store->resolveBlockType($this->block_type);
        array_push($frame->control_stack, new ControlStackEntry(
            break_target: $this->end,
            stack_height: count($stack) - count($block_type->params),
            arity: count($block_type->results),
        ));
    }
}
