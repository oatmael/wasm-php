<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

// pest()->extend(Tests\TestCase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

use Oatmael\WasmPhp\Util\WasmReader;

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function compileWat($wat)
{
    $tempFile = tempnam(sys_get_temp_dir(), 'wat');
    $outputFile = tempnam(sys_get_temp_dir(), 'wasm');
    file_put_contents($tempFile, $wat);
    exec(__DIR__ . "/../tools/wat2wasm --dump-module $tempFile -o $outputFile", $output);
    unlink($tempFile);

    $wasm = file_get_contents($outputFile);
    unlink($outputFile);

    return $wasm;
}

function wat2module($wat)
{
    $wasm = compileWat($wat);
    $reader = new WasmReader();
    return $reader->read($wasm);
}