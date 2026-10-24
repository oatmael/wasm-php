<?php

use Oatmael\WasmPhp\Util\WasmReader;

function compileWat($wat)
{
    $wat2wasm = getenv('WAT2WASM') ?: 'wat2wasm';
    $tempFile = tempnam(sys_get_temp_dir(), 'wat');
    $outputFile = tempnam(sys_get_temp_dir(), 'wasm');
    file_put_contents($tempFile, $wat);
    exec(escapeshellarg($wat2wasm) . ' ' . escapeshellarg($tempFile) . ' -o ' . escapeshellarg($outputFile) . ' 2>&1', $output, $exit_code);
    unlink($tempFile);

    $wasm = file_get_contents($outputFile);
    unlink($outputFile);

    if ($exit_code !== 0) {
        throw new RuntimeException("$wat2wasm failed (is WABT installed?):\n" . implode("\n", $output));
    }

    return $wasm;
}

function wat2module($wat)
{
    $wasm = compileWat($wat);
    $reader = new WasmReader();
    return $reader->read($wasm);
}
