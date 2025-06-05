# PHP Runtime for WASM
This repo is a WASM runtime (similar to [Wasmer](https://github.com/wasmerio/wasmer-php) or [Wasmtime](https://github.com/bytecodealliance/wasmtime)) designed to run entirely within PHP.

*🚧 This repo is currently a WIP. It is not recommended for production use. 🚧*

## Installation
WASM-PHP is not currently published. You can install it by specifying the repository in your `composer.json`:
```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/oatmael/wasm-php.git"
    }
  ],
  "minimum-stability": "dev",
  "require": {
    "oatmael/wasm-php": "dev-main"
  }
}
```

Installing will attempt to download and setup the [WASM Binary Toolkit](https://github.com/WebAssembly/wabt). This is not required for general usage, and is only required to run the test suite (though you may make use of toolkit as you see fit under your `./vendor/bin`).

## Usage
Using the `WasmReader`, create a module:

```php
$wasm = file_get_contents('math.wasm');

$reader = new WasmReader();
$module = $reader->read($wasm);
```

You may then use the module to execute exported functions:
```php
$left = new I32(26);
$right = new I32(4);

$results = $module->execute('subtract', [$left, $right]);
echo $results[0]->value; // 2
```

Or utilise imported functions:
```php
$left = new I32(26);
$right = new I32(4);

$results = $module
  ->setImport('env', 'exportedAdd', function (Store $store) use ($left, $right) {
      return new I32($left->value + $right->value);
  })
  ->execute('add', []);

echo $results[0]->value; // 30
```

## Why run WASM inside PHP?
WASM in it's current state has proven itself to be an incredibly useful for running sandboxed, optimised code.

Most often, this means utilising bare metal for highly performant JavaScript applications inside the browser or on the server with Node.js.

However, the inherently sandboxed nature of WASM also allows for more targeted use cases - such as running untrusted code, which provides powerful oppurtunity for user-submitted logic to be executed on the server.

This is where PHP comes in - it may not be the flashiest tool in box, but if [W3Techs surveys](https://w3techs.com/technologies/overview/programming_language) are to be trusted, it takes up the lions share of the web space.

There are currently methods of executing WASM in PHP, however the vast majority of them either have limited support, or reach out to external runtimes to execute modules. This comes with a variety of limitations - FFI can be unwieldy and mask errors, extensions can be cumbersome to install and support, some runtimes don't support all architectures, and execing out to the shell is not ideal.

Running the entirety of the runtime within PHP itself not only solves these issues, but allows for additional features and abstractions to be built on top to super-charge functionality.

## Limitations
PHP is not a great language. It's undeniably getting better, and working with modern PHP is night and day compared to the dark ages of PHP 5 - but it is not designed for the things WASM is designed to do, and as such comes with some inherent downsides:

### PHP numbers are always signed
WASM currently only works with raw number types, `I32`, `I64`, `F32`, and `F64`. These are all representable in PHP, however, various operations in WASM will treat these values as signed or unsigned (such as `i32.div_s` and `i32.div_u`). Due to the PHP number type only supporting signed integers, unsigned `I32` operations will only work consistently on 64-bit platforms, and unsigned `I64` operations are likely to break at greater thresholds.

PHP can support for arbitrary precision integers through the use of external libraries (such as `bcmath`), but this is not currently supported.

### PHP is not optimised for bare-metal use cases
One of WASM's use cases is to run highly performant code outside of the limitations of it's host language. Since this runtime runs entirely within the bounds of PHP, it inherits all of the languages limitations, chief among them the fact that PHP is not ideal for heavy bare metal usage. This language was built to handle web requests - and it's great at that - but when it comes to executing raw bare-metal operations, don't expect it to win any awards.

This runtime is not currently optimised for speed, and it may never be. It will not beat competing libraries on benchmarks, nor should it be expected to.

