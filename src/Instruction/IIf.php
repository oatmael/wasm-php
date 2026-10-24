<?php

namespace Oatmael\WasmPhp\Instruction;

use Oatmael\WasmPhp\Execution\ControlStackEntry;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Util\ValueType;
use Oatmael\WasmPhp\Util\WasmReader;

class IIf implements InstructionInterface {
    public function __construct(
        public readonly ValueType|int $block_type,
    ) {}

    public static function fromInput(string $input, int &$offset): InstructionInterface {
        $block_type = WasmReader::readBlockType($input, $offset);
        return new self($block_type);
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        /** @var Frame $frame */
        $frame = end($call_stack);

        $break_target = null;
        $else_block = null;
        $block_count = 1;
        $if_count = 1;
        for ($i = $frame->program_counter + 1; $i < count($frame->instructions); $i++) {
            $instruction = $frame->instructions[$i];
            if ($instruction instanceof Block || $instruction instanceof Loop) {
                $block_count++;
            } elseif ($instruction instanceof IIf) {
                $if_count++;
                $block_count++;
            } elseif ($instruction instanceof IElse) {
                if ($if_count === 1 && $else_block === null) {
                    $else_block = $i;
                }
            } elseif ($instruction instanceof End) {
                $if_count--;
                $block_count--;
                if ($block_count === 0) {
                    $break_target = $i;
                    break;
                }
            }
        }

        $block_type = $store->resolveBlockType($this->block_type);

        $condition = array_pop($stack);
        if ($else_block && !$condition->value) {
            $frame->program_counter = $else_block;
        } else if ($else_block === null && !$condition->value) {
            $frame->program_counter = $break_target;
        } else {
            array_push($frame->control_stack, new ControlStackEntry(
                break_target: $break_target,
                return_type: $block_type,
                if_block: true
            ));
        }
    }
}