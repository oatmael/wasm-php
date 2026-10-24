<?php

// Runs the official WebAssembly spec tests against the runtime.
// Usage: php tests/Spec/run.php [-v] [file.wast|directory ...]
// With no paths, every test in the testsuite downloaded by tests/Spec/fetch-testsuite.sh is run.

use Tests\Spec\SpecRunner;

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/SpecRunner.php';

ini_set('memory_limit', '2G');

$args = array_slice($argv, 1);
$verbose = in_array('-v', $args, true);
$paths = array_values(array_filter($args, static fn (string $arg) => $arg !== '-v')) ?: [__DIR__ . '/testsuite/test/core'];

$files = [];
foreach ($paths as $path) {
    if (is_dir($path)) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if ($file->getExtension() === 'wast') {
                $files[] = $file->getPathname();
            }
        }
    } elseif (is_file($path)) {
        $files[] = $path;
    } else {
        fwrite(STDERR, "No such file or directory: $path\n");
        exit(2);
    }
}
sort($files);

$total_passed = $total_failed = $total_skipped = 0;
foreach ($files as $file) {
    $runner = new SpecRunner(getenv('WASM_TOOLS') ?: 'wasm-tools');
    $runner->run($file);

    $failed = count($runner->failures);
    printf("%s %-50s %6d passed %6d failed %6d skipped\n", $failed ? 'FAIL' : 'PASS', basename(dirname($file)) . '/' . basename($file), $runner->passed, $failed, $runner->skipped);
    if ($verbose) {
        foreach ($runner->failures as $failure) {
            echo "    $failure\n";
        }
    }

    $total_passed += $runner->passed;
    $total_failed += $failed;
    $total_skipped += $runner->skipped;
}

printf("\n%d files: %d passed, %d failed, %d skipped (assert_invalid and assert_malformed need validation)\n", count($files), $total_passed, $total_failed, $total_skipped);
exit($total_failed ? 1 : 0);
