<?php

$os = PHP_OS;
$wabtVersion = '1.0.37';
$baseUrl = 'https://github.com/WebAssembly/wabt/releases/download';

// Create tools directory if it doesn't exist
if (!file_exists('tools')) {
  mkdir('tools');
} else {
  echo 'Tools already installed' . PHP_EOL;
  return;
}

// Create a unique temporary directory
$tempDir = sys_get_temp_dir() . '/wabt-setup-' . uniqid();
if (!mkdir($tempDir)) {
  die('Failed to create temporary directory' . PHP_EOL);
}

try {
  // Determine platform-specific download
  if (stripos($os, 'DAR') !== false) {
    $platform = 'macos-14';
  } elseif (stripos($os, 'WIN') !== false) {
    $platform = 'windows';
  } else {
    $platform = 'ubuntu-20.04';
  }

  $filename = 'wabt-' . $wabtVersion . '-' . $platform . '.tar.gz';
  $url = $baseUrl . '/' . $wabtVersion . '/' . $filename;
  $downloadPath = $tempDir . '/' . $filename;

  echo 'Downloading WABT from: ' . $url . PHP_EOL;

  if (!file_put_contents($downloadPath, file_get_contents($url))) {
    throw new Exception('Failed to download WABT');
  }

  $phar = new PharData($downloadPath);
  $phar->extractTo($tempDir, null, true);

  // Find the extracted directory
  $extractedDir = glob($tempDir . '/wabt-*', GLOB_ONLYDIR)[0];
  if (!$extractedDir || !is_dir($extractedDir . '/bin')) {
    throw new Exception('Failed to locate bin directory in extracted files');
  }

  // Move only the binary files from bin/ to tools/
  $binFiles = scandir($extractedDir . '/bin');
  foreach ($binFiles as $file) {
    if ($file !== '.' && $file !== '..') {
      $source = $extractedDir . '/bin/' . $file;
      $dest = 'tools/' . $file;
      if (!rename($source, $dest)) {
        throw new Exception("Failed to move binary file: $file");
      }
    }
  }

  // Make binaries executable on Unix-like systems
  if ($platform !== 'windows') {
    $binaries = ['wat2wasm', 'wasm2wat', 'wasm-objdump'];
    foreach ($binaries as $binary) {
      $binaryPath = 'tools/' . $binary;
      if (file_exists($binaryPath)) {
        chmod($binaryPath, 0755);
      }
    }
  }

  echo 'WABT tools installed successfully!' . PHP_EOL;
} catch (Exception $e) {
  echo 'Error: ' . $e->getMessage() . PHP_EOL;
  // Clean up tools directory if something went wrong
  array_map('unlink', glob('tools/*'));
} finally {
  // Clean up temporary files
  if (file_exists($downloadPath)) {
    unlink($downloadPath);
  }

  if (is_dir($extractedDir)) {
    // Remove all files in bin directory
    array_map('unlink', glob($extractedDir . '/bin/*'));
    rmdir($extractedDir . '/bin');

    // Remove all other files and directories recursively
    $iterator = new RecursiveIteratorIterator(
      new RecursiveDirectoryIterator($extractedDir, RecursiveDirectoryIterator::SKIP_DOTS),
      RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $fileinfo) {
      if ($fileinfo->isDir()) {
        rmdir($fileinfo->getRealPath());
      } else {
        unlink($fileinfo->getRealPath());
      }
    }
    rmdir($extractedDir);
  }

  // Remove temp directory
  if (is_dir($tempDir)) {
    rmdir($tempDir);
  }
}
