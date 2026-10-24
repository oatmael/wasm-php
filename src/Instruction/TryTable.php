<?php

namespace Oatmael\WasmPhp\Instruction;

use Exception;
use Oatmael\WasmPhp\Execution\ControlStackEntry;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Type\CatchClause;
use Oatmael\WasmPhp\Type\RefType;
use Oatmael\WasmPhp\Util\ValueType;
use Oatmael\WasmPhp\Util\WasmReader;

// A block with exception handlers. Store::run() searches the control stack for them when an exception is thrown.
class TryTable implements InstructionInterface
{
    // Index of the matching end instruction, linked by WasmReader once the function body is decoded
    public int $end;

    public function __construct(
        public readonly ValueType|RefType|int $block_type,
        /** @var CatchClause[] */
        public readonly array $catches,
    ) {}

    public static function fromInput(string $input, int &$offset): InstructionInterface
    {
        $block_type = WasmReader::readBlockType($input, $offset);
        $catches = [];
        $count = WasmReader::readLEB128Uint32($input, $offset);
        for ($i = 0; $i < $count; $i++) {
            // 0x00 catch, 0x01 catch_ref, 0x02 catch_all, 0x03 catch_all_ref
            $kind = WasmReader::readUint8($input, $offset);
            if ($kind > 0x03) {
                throw new Exception('Invalid catch clause kind: ' . $kind);
            }

            $tag_idx = $kind < 0x02 ? WasmReader::readLEB128Uint32($input, $offset) : null;
            $catches[] = new CatchClause($tag_idx, (bool) ($kind & 0x01), WasmReader::readLEB128Uint32($input, $offset));
        }

        return new self($block_type, $catches);
    }

    public function execute(array &$stack, array &$call_stack, Store $store)
    {
        $block_type = $store->resolveBlockType($this->block_type);
        end($call_stack)->control_stack[] = new ControlStackEntry(
            break_target: $this->end,
            stack_height: count($stack) - count($block_type->params),
            arity: count($block_type->results),
            catches: $this->catches,
        );
    }
}
