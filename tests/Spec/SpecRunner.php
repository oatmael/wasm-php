<?php

namespace Tests\Spec;

use Brick\Math\BigInteger;
use Exception;
use Oatmael\WasmPhp\Exception\CallStackExceededException;
use Oatmael\WasmPhp\Exception\LinkException;
use Oatmael\WasmPhp\Exception\TrapException;
use Oatmael\WasmPhp\Exception\UncaughtException;
use Oatmael\WasmPhp\Module;
use Oatmael\WasmPhp\Type\ArrayRef;
use Oatmael\WasmPhp\Type\ExnRef;
use Oatmael\WasmPhp\Type\ExternRef;
use Oatmael\WasmPhp\Type\F32;
use Oatmael\WasmPhp\Type\F64;
use Oatmael\WasmPhp\Type\FuncRef;
use Oatmael\WasmPhp\Type\GlobalVariable;
use Oatmael\WasmPhp\Type\HostRef;
use Oatmael\WasmPhp\Type\I31Ref;
use Oatmael\WasmPhp\Type\I32;
use Oatmael\WasmPhp\Type\I64;
use Oatmael\WasmPhp\Type\NullRef;
use Oatmael\WasmPhp\Type\StructRef;
use Oatmael\WasmPhp\Type\V128;
use Oatmael\WasmPhp\Type\ValueInterface;
use Oatmael\WasmPhp\Util\WasmReader;
use Throwable;

// Runs a .wast script from the official spec testsuite. Scripts are converted to JSON and binary modules with
// `wasm-tools json-from-wast`, the same format as WABT's wast2json.
// Modules are not validated, so assert_invalid and assert_malformed commands are skipped.
class SpecRunner
{
    public int $passed = 0;
    public int $skipped = 0;
    public array $failures = [];

    protected array $modules = [];
    protected array $definitions = [];
    protected array $registered = [];
    protected ?Module $current = null;

    public function __construct(protected string $wasm_tools)
    {
    }

    public function run(string $wast): void
    {
        $dir = sys_get_temp_dir() . '/wasm-php-spec-' . getmypid() . '-' . md5($wast);
        @mkdir($dir);
        try {
            $this->exec(['json-from-wast', $wast, '-o', "$dir/test.json", '--wasm-dir', $dir]);
            $script = json_decode(file_get_contents("$dir/test.json"), true, flags: JSON_THROW_ON_ERROR);

            $this->modules = [];
            $this->definitions = [];
            $this->current = null;
            $this->registered = ['spectest' => $this->spectest($dir)];

            foreach ($script['commands'] as $command) {
                try {
                    $this->command($command, $dir) ? $this->passed++ : $this->skipped++;
                } catch (Throwable $e) {
                    $this->failures[] = sprintf('%s:%d %s - %s: %s', basename($wast), $command['line'], $command['type'], $e::class, $e->getMessage());
                }
            }
        } finally {
            array_map(unlink(...), glob("$dir/*"));
            @rmdir($dir);
        }
    }

    // Returns false if the command was skipped
    protected function command(array $command, string $dir): bool
    {
        switch ($command['type']) {
            case 'module':
                $this->current = null;
                $module = $this->instantiate($this->binary($command, $dir));
                $this->current = $module;
                if (isset($command['name'])) {
                    $this->modules[$command['name']] = $module;
                }
                return true;

            case 'module_definition':
                $this->definitions[$command['name'] ?? ''] = $this->binary($command, $dir);
                return true;

            case 'module_instance':
                $this->current = null;
                $module = $this->instantiate($this->definitions[$command['module'] ?? '']);
                $this->current = $module;
                $this->modules[$command['instance']] = $module;
                return true;

            case 'register':
                $this->registered[$command['as']] = isset($command['name']) ? $this->module($command['name']) : $this->current;
                return true;

            case 'action':
                $this->action($command['action']);
                return true;

            case 'assert_return':
                $results = $this->action($command['action']);
                $this->expectResults($results, $command['expected']);
                return true;

            case 'assert_trap':
                $text = $command['text'];
                if (isset($command['action'])) {
                    $this->expectThrow(fn () => $this->action($command['action']), TrapException::class, $text);
                } else {
                    $this->expectThrow(fn () => $this->instantiate($this->binary($command, $dir)), TrapException::class, $text);
                }
                return true;

            case 'assert_exhaustion':
                $this->expectThrow(fn () => $this->action($command['action']), CallStackExceededException::class, $command['text']);
                return true;

            case 'assert_exception':
                $this->expectThrow(fn () => $this->action($command['action']), UncaughtException::class, '');
                return true;

            case 'assert_uninstantiable':
                $this->expectThrow(fn () => $this->instantiate($this->binary($command, $dir)), TrapException::class, $command['text']);
                return true;

            case 'assert_unlinkable':
                $this->expectThrow(fn () => $this->instantiate($this->binary($command, $dir)), LinkException::class, '');
                return true;

            default:
                // assert_invalid and assert_malformed need validation, which isn't implemented
                return false;
        }
    }

    // Modules written in the text format also come compiled to a binary
    protected function binary(array $command, string $dir): string
    {
        return file_get_contents($dir . '/' . ($command['binary_filename'] ?? $command['filename']));
    }

    protected function instantiate(string $wasm): Module
    {
        $module = (new WasmReader())->read($wasm);
        foreach ($module->getImports() as $import) {
            $exporter = $this->registered[$import->module] ?? throw new LinkException("unknown import {$import->module}.{$import->field}");
            try {
                $export = $exporter->getExport($import->field);
            } catch (Exception) {
                throw new LinkException("unknown import {$import->module}.{$import->field}");
            }

            $module->setImport($import->module, $import->field, $export);
        }

        return $module->instantiate();
    }

    protected function action(array $action): array
    {
        $module = isset($action['module']) ? $this->module($action['module']) : $this->current;

        return match ($action['type']) {
            'invoke' => $module->execute($action['field'], array_map($this->value(...), $action['args'])) ?? [],
            'get'    => (function () use ($module, $action) {
                $global = $module->getExport($action['field']);
                return $global instanceof GlobalVariable ? [$global->value] : throw new Exception("{$action['field']} is not a global");
            })(),
        };
    }

    protected function module(string $name): Module
    {
        return $this->modules[$name] ?? throw new Exception("Module $name failed to instantiate");
    }

    protected function expectThrow(callable $callback, string $class, string $text): void
    {
        try {
            $callback();
        } catch (Throwable $e) {
            if (!($e instanceof $class) || !str_contains($e->getMessage(), $text)) {
                throw new Exception("Expected $class '$text', got " . $e::class . " '{$e->getMessage()}'", previous: $e);
            }

            return;
        }

        throw new Exception("Expected $class '$text', but nothing was thrown");
    }

    protected function expectResults(array $results, array $expected): void
    {
        if (count($results) !== count($expected)) {
            throw new Exception('Expected ' . count($expected) . ' results, got ' . count($results));
        }

        foreach ($expected as $i => $value) {
            $alternatives = $value['type'] === 'either' ? $value['values'] : [$value];
            if (!array_any($alternatives, fn (array $alternative) => $this->matches($results[$i], $alternative))) {
                throw new Exception("Result $i: expected " . json_encode($value) . ', got ' . self::describe($results[$i]));
            }
        }
    }

    protected function matches(ValueInterface $result, array $expected): bool
    {
        $value = $expected['value'] ?? null;
        return match ($expected['type']) {
            'i32'  => $result instanceof I32 && ($result->value & 0xFFFFFFFF) === ((int) $value & 0xFFFFFFFF),
            'i64'  => $result instanceof I64 && $result->value === self::int64($value),
            'f32'  => $result instanceof F32 && self::floatMatches($result->getBits(), $value, 32),
            'f64'  => $result instanceof F64 && self::floatMatches($result->getBits(), $value, 64),
            'v128' => $result instanceof V128 && $this->vectorMatches($result, $expected['lane_type'], $value),
            default => $this->refMatches($result, $expected['type'], $value),
        };
    }

    protected function refMatches(ValueInterface $result, string $type, ?string $value): bool
    {
        if ($value === 'null' || str_starts_with($type, 'null') || $type === 'refnull') {
            return $result instanceof NullRef;
        }

        return match ($type) {
            'funcref'   => $result instanceof FuncRef,
            'externref' => $result instanceof ExternRef && ($value === null || $result->value === (int) $value),
            'anyref'    => $value === null ?
                $result instanceof HostRef || $result instanceof I31Ref || $result instanceof StructRef || $result instanceof ArrayRef :
                $result instanceof HostRef && $result->value === (int) $value,
            'eqref'     => $result instanceof I31Ref || $result instanceof StructRef || $result instanceof ArrayRef,
            'i31ref'    => $result instanceof I31Ref,
            'structref' => $result instanceof StructRef,
            'arrayref'  => $result instanceof ArrayRef,
            'exnref'    => $result instanceof ExnRef,
            default     => throw new Exception("Unsupported expected type $type"),
        };
    }

    protected function vectorMatches(V128 $result, string $lane_type, array $lanes): bool
    {
        [$format, $bits] = self::laneFormat($lane_type);
        $actual = array_values(unpack($format . count($lanes), $result->bytes));
        foreach ($lanes as $i => $lane) {
            $matches = match ($lane_type) {
                'f32', 'f64' => self::floatMatches($actual[$i], $lane, $bits),
                'i64'        => $actual[$i] === self::int64($lane),
                default      => $actual[$i] === ((int) $lane & ((1 << $bits) - 1)),
            };
            if (!$matches) {
                return false;
            }
        }

        return true;
    }

    protected static function floatMatches(int $bits, string $expected, int $width): bool
    {
        [$exponent, $quiet] = $width === 32 ? [0x7F800000, 0x00400000] : [0x7FF0000000000000, 0x0008000000000000];
        $magnitude = $width === 32 ? $bits & 0x7FFFFFFF : $bits & PHP_INT_MAX;

        return match ($expected) {
            'nan:canonical'  => $magnitude === ($exponent | $quiet),
            'nan:arithmetic' => ($magnitude & ($exponent | $quiet)) === ($exponent | $quiet),
            default          => $bits === ($width === 32 ? (int) $expected : self::int64($expected)),
        };
    }

    protected function value(array $value): ValueInterface
    {
        $raw = $value['value'] ?? null;
        return match ($value['type']) {
            'i32'       => new I32((int) $raw),
            'i64'       => new I64(self::int64($raw)),
            'f32'       => F32::fromBits((int) $raw),
            'f64'       => F64::fromBits(self::int64($raw)),
            'v128'      => new V128(pack(self::laneFormat($value['lane_type'])[0] . '*', ...array_map(
                fn (string $lane) => in_array($value['lane_type'], ['i64', 'f64'], true) ? self::int64($lane) : (int) $lane,
                $raw,
            ))),
            'externref' => $raw === 'null' ? new NullRef() : new ExternRef((int) $raw),
            'anyref'    => $raw === 'null' ? new NullRef() : new HostRef((int) $raw),
            default     => $raw === 'null' || $raw === null ? new NullRef() : throw new Exception("Unsupported argument type {$value['type']}"),
        };
    }

    // Lanes are compared as raw bits, so float lanes are unpacked as integers
    protected static function laneFormat(string $lane_type): array
    {
        return match ($lane_type) {
            'i8'         => ['C', 8],
            'i16'        => ['v', 16],
            'i32', 'f32' => ['V', 32],
            'i64', 'f64' => ['P', 64],
        };
    }

    // i64 values (and f64 bits) are written as unsigned decimals, which can exceed PHP_INT_MAX
    protected static function int64(string $value): int
    {
        return I64::fromBigInteger(BigInteger::of($value))->value;
    }

    protected static function describe(ValueInterface $value): string
    {
        return match (true) {
            $value instanceof F32  => 'f32 bits ' . $value->getBits() . ' (' . var_export($value->value, true) . ')',
            $value instanceof F64  => 'f64 bits ' . $value->getBits() . ' (' . var_export($value->value, true) . ')',
            $value instanceof V128 => 'v128 ' . bin2hex($value->bytes),
            $value instanceof I32, $value instanceof I64 => $value::class . ' ' . $value->getValue(),
            default                => get_debug_type($value) . ' ' . json_encode($value->getValue()),
        };
    }

    // The module the spec tests import from: some globals, a table, a memory and printing functions
    protected function spectest(string $dir): Module
    {
        file_put_contents("$dir/spectest.wat", <<<WAT
            (module
                (global (export "global_i32") i32 (i32.const 666))
                (global (export "global_i64") i64 (i64.const 666))
                (global (export "global_f32") f32 (f32.const 666.6))
                (global (export "global_f64") f64 (f64.const 666.6))
                (table (export "table") 10 20 funcref)
                (table (export "table64") i64 10 20 funcref)
                (memory (export "memory") 1 2)
                (func (export "print"))
                (func (export "print_i32") (param i32))
                (func (export "print_i64") (param i64))
                (func (export "print_f32") (param f32))
                (func (export "print_f64") (param f64))
                (func (export "print_i32_f32") (param i32 f32))
                (func (export "print_f64_f64") (param f64 f64))
            )
            WAT);
        $this->exec(['parse', "$dir/spectest.wat", '-o', "$dir/spectest.wasm"]);

        return (new WasmReader())->read(file_get_contents("$dir/spectest.wasm"))->instantiate();
    }

    protected function exec(array $args): void
    {
        exec(implode(' ', array_map(escapeshellarg(...), [$this->wasm_tools, ...$args])) . ' 2>&1', $output, $exit_code);
        if ($exit_code !== 0) {
            throw new Exception("wasm-tools failed:\n" . implode("\n", $output));
        }
    }
}
