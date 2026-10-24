<?php

use Oatmael\WasmPhp\Instruction\Block;
use Oatmael\WasmPhp\Instruction\Extension\I32TruncSatF32S;
use Oatmael\WasmPhp\Instruction\Extension\MemoryCopy;
use Oatmael\WasmPhp\Instruction\I64Load;
use Oatmael\WasmPhp\Instruction\IIf;
use Oatmael\WasmPhp\Instruction\Simd\V128Const;
use Oatmael\WasmPhp\Instruction\Simd\V128Store;
use Oatmael\WasmPhp\Util\ValueType;
use Oatmael\WasmPhp\Util\WasmReader;

test('decodes standard opcodes with reserved class names', function () {
    $offset = 0;
    $instruction = WasmReader::readInstruction("\x04\x40", $offset);

    expect($instruction)->toBeInstanceOf(IIf::class);
    expect($instruction->block_type)->toBe(ValueType::VOID);
    expect($offset)->toBe(2);
});

test('decodes extension prefixed opcodes', function () {
    $offset = 0;
    expect(WasmReader::readInstruction("\xfc\x00", $offset))->toBeInstanceOf(I32TruncSatF32S::class);

    $offset = 0;
    $instruction = WasmReader::readInstruction("\xfc\x0a\x01\x02", $offset);
    expect($instruction)->toBeInstanceOf(MemoryCopy::class);
    expect($instruction->dst_memory_idx)->toBe(1);
    expect($instruction->src_memory_idx)->toBe(2);
    expect($offset)->toBe(4);
});

test('decodes simd prefixed opcodes', function () {
    $offset = 0;
    $instruction = WasmReader::readInstruction("\xfd\x0c" . str_repeat("\x01", 16), $offset);
    expect($instruction)->toBeInstanceOf(V128Const::class);
    expect($instruction->bytes)->toBe(str_repeat("\x01", 16));
    expect($offset)->toBe(18);

    $offset = 0;
    $instruction = WasmReader::readInstruction("\xfd\x0b\x04\x88\x01", $offset);
    expect($instruction)->toBeInstanceOf(V128Store::class);
    expect($instruction->offset)->toBe(136);
    expect($offset)->toBe(5);
});

test('decodes memory immediates as LEB128', function () {
    $offset = 0;
    $instruction = WasmReader::readInstruction("\x29\x03\x88\x01", $offset);

    expect($instruction)->toBeInstanceOf(I64Load::class);
    expect($instruction->align)->toBe(3);
    expect($instruction->offset)->toBe(136);
    expect($offset)->toBe(4);
});

test('decodes block type indices', function () {
    $offset = 0;
    $instruction = WasmReader::readInstruction("\x02\x05", $offset);

    expect($instruction)->toBeInstanceOf(Block::class);
    expect($instruction->block_type)->toBe(5);
});

test('rejects unknown opcodes', function () {
    $offset = 0;
    WasmReader::readInstruction("\x06", $offset);
})->throws(Exception::class, 'Unknown opcode 0x06');

test('executes multi-value blocks', function () {
    $module = wat2module(<<<WAT
        (module
            (func (export "f") (result i32)
                (block (result i32 i32)
                    (i32.const 1)
                    (i32.const 2)
                )
                (i32.add)
            )
        )
        WAT);

    expect($module->execute('f', [])[0]->getValue())->toBe(3);
});

test('decodes global initialisers', function (string $type, string $init, $expected) {
    $module = wat2module(<<<WAT
        (module
            (global $type ($type.const $init))
            (func (export "f") (result $type) (global.get 0))
        )
        WAT);

    expect($module->execute('f', [])[0]->getValue())->toBe($expected);
})->with([
    'negative i32' => ['i32', '-1', -1],
    'large i32'    => ['i32', '0x7fffffff', 0x7fffffff],
    'negative i64' => ['i64', '-1', -1],
    'large i64'    => ['i64', '0x7fffffffffffffff', PHP_INT_MAX],
    'f32'          => ['f32', '1.5', 1.5],
    'f64'          => ['f64', '-2.25', -2.25],
]);

test('rejects unsupported imports', function () {
    wat2module(<<<WAT
        (module
            (import "env" "memory" (memory 1))
        )
        WAT);
})->throws(Exception::class, 'Unsupported import env.memory');
