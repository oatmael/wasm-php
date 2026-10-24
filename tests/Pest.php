<?php

use Oatmael\WasmPhp\Type\ValueInterface;
use Oatmael\WasmPhp\Util\WasmReader;

function compileWat($wat)
{
    $wasm_tools = getenv('WASM_TOOLS') ?: 'wasm-tools';
    $tempFile = tempnam(sys_get_temp_dir(), 'wat');
    $outputFile = tempnam(sys_get_temp_dir(), 'wasm');
    file_put_contents($tempFile, $wat);
    exec(escapeshellarg($wasm_tools) . ' parse ' . escapeshellarg($tempFile) . ' -o ' . escapeshellarg($outputFile) . ' 2>&1', $output, $exit_code);
    unlink($tempFile);

    $wasm = file_get_contents($outputFile);
    unlink($outputFile);

    if ($exit_code !== 0) {
        throw new RuntimeException("$wasm_tools failed (is wasm-tools installed?):\n" . implode("\n", $output));
    }

    return $wasm;
}

function wat2module($wat)
{
    $wasm = compileWat($wat);
    $reader = new WasmReader();
    return $reader->read($wasm);
}

// Executes a single instruction over the given arguments and returns its result. The parameter types are taken
// from the arguments' classes.
function wasmOp(string $instruction, array $args = [], string $result = '', string $module_fields = ''): ?ValueInterface
{
    $params = implode(' ', array_map(static fn (ValueInterface $arg) => strtolower((new ReflectionClass($arg))->getShortName()), $args));
    $locals = implode(' ', array_map(static fn (int $i) => "(local.get $i)", array_keys($args)));
    $result = $result ? "(result $result)" : '';

    $module = wat2module(<<<WAT
        (module
            $module_fields
            (func (export "f") (param $params) $result
                ($instruction $locals)
            )
        )
        WAT);

    return $module->execute('f', $args)[0] ?? null;
}
