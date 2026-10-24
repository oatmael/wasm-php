<?php

use Brick\Math\BigInteger;
use Oatmael\WasmPhp\Exception\LinkException;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Import\Encoding;
use Oatmael\WasmPhp\Import\LengthPrefixed;
use Oatmael\WasmPhp\Import\NullTerminated;
use Oatmael\WasmPhp\Import\Unsigned;
use Oatmael\WasmPhp\Module;
use Oatmael\WasmPhp\Type\F64;
use Oatmael\WasmPhp\Type\I32;
use Oatmael\WasmPhp\Type\I64;
use Oatmael\WasmPhp\Type\ExternRef;
use Oatmael\WasmPhp\Type\Memory;
use Oatmael\WasmPhp\Type\NullRef;

// Imports env.f, and when given its arguments, exports "call" to call it with them and return its results
function importModule(string $params, string $results, ?string $args = null, string $memory = ''): Module
{
    $call = $args === null ? '' : "(func (export \"call\") (result $results) $args (call \$f))";

    return wat2module(<<<WAT
        (module
            (import "env" "f" (func \$f (param $params) (result $results)))
            $memory
            $call
        )
        WAT);
}

test('converts numbers', function () {
    $module = importModule('i32 i64 f32 f64', 'f64', '(i32.const -1) (i64.const 2) (f32.const 1.5) (f64.const 0.25)');
    $module->setImport('env', 'f', fn (int $a, int $b, float $c, float $d): float => $a + $b + $c + $d);

    expect($module->execute('call', [])[0]->getValue())->toBe(2.75);
});

test('converts unsigned and bool arguments', function () {
    $module = importModule('i32 i32', 'i32', '(i32.const -1) (i32.const 2)');
    $module->setImport('env', 'f', function (#[Unsigned] int $unsigned, bool $flag) {
        expect($unsigned)->toBe(0xFFFFFFFF);
        expect($flag)->toBeTrue();
        return false;
    });

    expect($module->execute('call', [])[0]->getValue())->toBe(0);
});

test('converts i64s to and from BigInteger', function () {
    $module = importModule('i64 i64', 'i64', '(i64.const -1) (i64.const -1)');
    $module->setImport('env', 'f', function (BigInteger $signed, #[Unsigned] BigInteger $unsigned): BigInteger {
        expect((string) $signed)->toBe('-1');
        expect((string) $unsigned)->toBe('18446744073709551615');

        // 2^64 - 2 wraps around to -2
        return $unsigned->plus($signed);
    });

    expect($module->execute('call', [])[0]->getValue())->toBe(-2);
});

test('passes value wrappers through', function () {
    $module = importModule('i32 i64', 'i64', '(i32.const 3) (i64.const 4)');
    $module->setImport('env', 'f', fn (I32 $a, $b) => new I64($a->getValue() + $b->getValue()));

    expect($module->execute('call', [])[0]->getValue())->toBe(7);
});

test('injects memory and the store', function () {
    $module = importModule('i32', 'i32', '(i32.const 0)', '(memory 1) (data (i32.const 0) "\2a")');
    $module->setImport('env', 'f', fn (Store $store, int $address, Memory $memory) => ord($memory->data[$address]));

    expect($module->execute('call', [])[0]->getValue())->toBe(42);
});

test('returns multiple values', function () {
    $module = importModule('', 'i32 f64', '');
    $module->setImport('env', 'f', fn () => [1, 2.5]);

    $ret = $module->execute('call', []);
    expect($ret[0]->getValue())->toBe(1);
    expect($ret[1])->toBeInstanceOf(F64::class);
    expect($ret[1]->getValue())->toBe(2.5);
});

test('ignores return values from void imports', function () {
    $module = importModule('i32', '', '(i32.const 1)');
    $calls = [];
    $module->setImport('env', 'f', function (int $value) use (&$calls) {
        $calls[] = $value;
        return 'ignored';
    });

    expect($module->execute('call', []))->toBeNull();
    expect($calls)->toBe([1]);
});

test('decodes strings', function (string $params, string $data, string $args, Closure $receiver, string $expected) {
    $module = importModule($params, '', $args, "(memory 1) (data (i32.const 8) \"$data\")");
    $received = new ArrayObject();
    $module->setImport('env', 'f', $receiver($received));

    $module->execute('call', []);
    expect($received->getArrayCopy())->toBe([$expected]);
})->with([
    'pointer and length' => [
        'i32 i32', 'hello world', '(i32.const 8) (i32.const 5)',
        fn (ArrayObject $received) => fn (string $value) => $received[] = $value,
        'hello',
    ],
    'null terminated' => [
        'i32', 'hi\00there', '(i32.const 8)',
        fn (ArrayObject $received) => fn (#[NullTerminated] string $value) => $received[] = $value,
        'hi',
    ],
    'length prefixed' => [
        'i32', '\03\00\00\00abcd', '(i32.const 8)',
        fn (ArrayObject $received) => fn (#[LengthPrefixed] string $value) => $received[] = $value,
        'abc',
    ],
    'utf-16 null terminated' => [
        'i32', 'h\00\e9\00\00\00', '(i32.const 8)',
        fn (ArrayObject $received) => fn (#[NullTerminated, Encoding('UTF-16LE')] string $value) => $received[] = $value,
        'hé',
    ],
    'utf-8 multibyte' => [
        'i32 i32', '\c3\a9t\c3\a9', '(i32.const 8) (i32.const 5)',
        fn (ArrayObject $received) => fn (string $value) => $received[] = $value,
        'été',
    ],
]);

test('binds strings alongside other arguments', function () {
    $module = importModule('i32 i32 i32 i32', 'i32', '(i32.const 7) (i32.const 8) (i32.const 3) (i32.const 8)', '(memory 1) (data (i32.const 8) "abc")');
    $module->setImport('env', 'f', fn (int $count, string $a, #[NullTerminated] string $b) => $count + strlen($a) + strlen($b));

    expect($module->execute('call', [])[0]->getValue())->toBe(13);
});

test('rejects invalid utf-8 strings', function () {
    $module = importModule('i32 i32', '', '(i32.const 0) (i32.const 1)', '(memory 1) (data (i32.const 0) "\ff")');
    $module->setImport('env', 'f', fn (string $value) => null);

    $module->execute('call', []);
})->throws(Exception::class, 'Invalid UTF-8 string');

test('rejects out of bounds strings', function () {
    $module = importModule('i32 i32', '', '(i32.const 65535) (i32.const 2)', '(memory 1)');
    $module->setImport('env', 'f', fn (string $value) => null);

    $module->execute('call', []);
})->throws(Exception::class, 'out of bounds memory access');

test('rejects mismatched signatures on registration', function (string $params, Closure $func, string $message) {
    expect(fn () => importModule($params, '')->setImport('env', 'f', $func))
        ->toThrow(Exception::class, $message);
})->with([
    'too few php params'  => ['i32 i32', fn (int $a) => null, 'only accepts 1 of its 2 arguments'],
    'too many php params' => ['i32', fn (int $a, int $b) => null, '$b can\'t be bound'],
    'wrong type'          => ['i32', fn (float $a) => null, 'it cannot receive an i32'],
    'wrong wrapper'       => ['i32', fn (I64 $a) => null, 'it cannot receive an i32'],
    'unsigned i64'        => ['i64', fn (#[Unsigned] int $a) => null, '#[Unsigned] is only supported'],
    'string from i64s'    => ['i64 i64', fn (string $a) => null, 'strings take 2 i32 arguments'],
    'union type'          => ['i32', fn (int|float $a) => null, 'union and intersection types'],
]);

test('explains signature mismatches', function () {
    importModule('i32 i32', '')->setImport('env', 'f', fn (float $a, int $b) => null);
})->throws(Exception::class, 'Import env.f has signature (i32, i32) -> (), but parameter $a can\'t be bound - it cannot receive an i32');

test('rejects unknown imports', function () {
    importModule('', '')->setImport('env', 'missing', fn () => null);
})->throws(Exception::class, 'Module does not import env.missing');

test('rejects unknown encodings on registration', function () {
    importModule('i32', '')->setImport('env', 'f', fn (#[NullTerminated, Encoding('NOT-AN-ENCODING')] string $a) => null);
})->throws(ValueError::class);

test('rejects bad return values', function () {
    $module = importModule('', 'i32', '');
    $module->setImport('env', 'f', fn () => 1.5);

    $module->execute('call', []);
})->throws(Exception::class, 'Import env.f returned float, expected i32');

test('passes references to and from host functions', function () {
    $module = importModule('externref', 'externref', '(ref.null extern)');
    $module->setImport('env', 'f', fn ($ref) => $ref instanceof NullRef ? new ExternRef('was null') : null);

    expect($module->execute('call', [])[0]->value)->toBe('was null');
});

test('rejects references bound to scalar parameters', function () {
    importModule('externref', '')->setImport('env', 'f', fn (int $a) => null);
})->throws(Exception::class, 'it cannot receive a (ref null extern)');

test('links memories, tables, globals and functions from another module', function () {
    $exporter = wat2module(<<<WAT
        (module
            (memory (export "memory") 1)
            (table (export "table") 1 funcref)
            (global (export "counter") (mut i32) (i32.const 10))
            (func \$seven (export "seven") (result i32) (i32.const 7))
            (elem (i32.const 0) \$seven)
            (func (export "load") (result i32) (i32.load (i32.const 0)))
        )
        WAT);

    $importer = wat2module(<<<WAT
        (module
            (import "lib" "memory" (memory 1))
            (import "lib" "table" (table 1 funcref))
            (import "lib" "counter" (global \$counter (mut i32)))
            (import "lib" "seven" (func \$seven (result i32)))
            (func (export "run") (result i32)
                (i32.store (i32.const 0) (i32.const 42))
                (global.set \$counter (i32.add (global.get \$counter) (i32.const 1)))
                (i32.add (call \$seven) (call_indirect (result i32) (i32.const 0)))
            )
        )
        WAT);

    foreach ($importer->getImports() as $import) {
        $importer->setImport($import->module, $import->field, $exporter->getExport($import->field));
    }

    expect($importer->execute('run', [])[0]->getValue())->toBe(14);
    // The memory and global are shared
    expect($exporter->execute('load', [])[0]->getValue())->toBe(42);
    expect($exporter->getExport('counter')->value->getValue())->toBe(11);
});

test('provides host memories', function () {
    $memory = new Memory(1);
    $module = wat2module(<<<WAT
        (module
            (import "env" "memory" (memory 1))
            (func (export "store") (i32.store8 (i32.const 3) (i32.const 9)))
        )
        WAT);

    $module->setImport('env', 'memory', $memory)->execute('store', []);
    expect(ord($memory->data[3]))->toBe(9);
});

test('rejects incompatible imports', function () {
    $module = wat2module('(module (import "env" "memory" (memory 2)))');
    $module->setImport('env', 'memory', new Memory(1));
})->throws(LinkException::class, 'incompatible import type');

test('rejects missing imports on instantiation', function () {
    wat2module('(module (import "env" "g" (global i32)))')->instantiate();
})->throws(LinkException::class, 'unknown import env.g');

test('shares exception tags', function () {
    $thrower = wat2module(<<<WAT
        (module
            (tag \$e (export "e") (param i32))
            (func (export "throw") (param i32) (throw \$e (local.get 0)))
        )
        WAT);
    $catcher = wat2module(<<<WAT
        (module
            (import "lib" "e" (tag \$e (param i32)))
            (import "lib" "throw" (func \$throw (param i32)))
            (func (export "catch") (result i32)
                (block \$handler (result i32)
                    (try_table (catch \$e \$handler) (call \$throw (i32.const 5)))
                    (i32.const 0)
                )
            )
        )
        WAT);

    $catcher->setImport('lib', 'e', $thrower->getExport('e'))->setImport('lib', 'throw', $thrower->getExport('throw'));
    expect($catcher->execute('catch', [])[0]->getValue())->toBe(5);
});
