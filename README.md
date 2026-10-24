# PHP Runtime for WASM
This repo is a WebAssembly runtime (similar to [Wasmer](https://github.com/wasmerio/wasmer-php) or [Wasmtime](https://github.com/bytecodealliance/wasmtime)) written entirely in PHP. No WASM extension, FFI or external binary is needed to run a module.

*🚧 This repo is currently a WIP. It is not recommended for production use. 🚧*

## Installation
WASM-PHP requires 64-bit PHP 8.5 or later with the `mbstring` extension.

It is not currently published on Packagist. You can install it by adding the repository to your `composer.json`:
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

The only runtime dependency is [`brick/math`](https://github.com/brick/math), which handles unsigned 64-bit arithmetic.

## Usage
### Loading a module
Read a binary `.wasm` file into a `Module` with the `WasmReader`:

```php
use Oatmael\WasmPhp\Util\WasmReader;

$module = (new WasmReader())->read(file_get_contents('math.wasm'));
```

Only the binary format is supported. To run a `.wat` text file, compile it first, e.g. with `wat2wasm` from [WABT](https://github.com/WebAssembly/wabt).

### Calling exported functions
Call an exported function by name with `execute()`. Arguments are passed as value objects (`I32`, `I64`, `F32`, `F64` or `V128`, or a reference such as `NullRef`, `ExternRef` or `FuncRef`), which must match the function's signature:

```php
use Oatmael\WasmPhp\Type\I32;

$results = $module->execute('subtract', [new I32(26), new I32(4)]);

echo $results[0]->getValue(); // 22
```

`execute()` returns an array of results in the order the function declares them, or `null` for functions without results.

The module is instantiated the first time it's used: globals, tables and memories are initialised, active element and data segments are copied in, and the start function runs. Call `instantiate()` to do this explicitly, for example to catch a trap in the start function.

### Errors
A trap (such as an out of bounds memory access, or `unreachable`) throws an `Oatmael\WasmPhp\Exception\TrapException`, whose message starts with the spec's wording (`integer divide by zero`, `out of bounds memory access`, and so on). Running out of call stack throws a `CallStackExceededException`, which is also a trap.

A wasm exception (from `throw`) that isn't caught by a `try_table` throws an `UncaughtException`. Its `exception` property holds the tag it was thrown with and its values.

A module's memory can be read directly once it has run:

```php
$bytes = $module->getMemory()->read($pointer, $length);
```

### Linking modules
Any import can be provided by another module's export, with `getExport()`. Functions, tables, memories, globals and tags are shared, so both modules see the same state. Wasm functions always run in the module that defined them:

```php
foreach ($app->getImports() as $import) {
    if ($import->module === 'lib') {
        $app->setImport('lib', $import->field, $lib->getExport($import->field));
    }
}
```

Tables, memories and globals can also be created in PHP (`new Memory(1)`, `new Table(...)`, `new GlobalVariable(...)`) and shared between modules. Imports are checked against the module's declared types, and a mismatch throws a `LinkException`, as does instantiating a module with a missing import.

### Providing imports
Imported functions are provided as PHP callables, registered with `setImport()` before the module is instantiated. The callable's parameter and return types tell the runtime how to convert between WASM and PHP values:

```php
// (import "env" "add" (func (param i32 i32) (result i32)))
$module->setImport('env', 'add', fn (int $left, int $right): int => $left + $right);
```

| PHP parameter | WASM arguments | Receives |
|---|---|---|
| `int` | `i32` or `i64` | The signed value |
| `#[Unsigned] int` | `i32` | The unsigned value (0 to 2<sup>32</sup> - 1) |
| `float` | `f32` or `f64` | The value |
| `bool` | `i32` | `true` for any non-zero value |
| `BigInteger` | `i64` | The signed value, or the unsigned value with `#[Unsigned]` |
| `I32`, `I64`, `F32`, `F64`, `V128`, or untyped | One of the matching type | The value object |
| `ValueInterface`, a reference class, or untyped | A reference | The reference, such as a `NullRef`, `ExternRef` or `FuncRef` |
| `string` | Two `i32`s by default (see below) | The string, read from memory |
| `Memory` or `Store` | None | The module's memory or store, injected |

Return values are converted to the import's result types: an `int`, `float`, `bool`, `BigInteger` or value object for a single result (or `null` for a nullable reference), or an array for multiple results. Values returned by imports without results are ignored.

The callable is checked against the import's signature when it is registered. A mismatch throws immediately, with a message describing what the module expects. For example, registering `fn (int $left, float $right) => ...` for the import above throws:

```
Import env.add has signature (i32, i32) -> (i32), but parameter $right can't be bound - it cannot receive an i32
```

#### Strings
A `string` parameter reads its value from the module's memory. By default it takes two `i32` arguments, a pointer and a length in bytes, and decodes the bytes as UTF-8. Attributes change how the string is laid out and encoded:

```php
use Oatmael\WasmPhp\Import\Encoding;
use Oatmael\WasmPhp\Import\LengthPrefixed;
use Oatmael\WasmPhp\Import\NullTerminated;

// (param i32 i32) - pointer and byte length
$module->setImport('env', 'log', fn (string $message) => print($message));

// (param i32) - pointer to a null-terminated string
$module->setImport('env', 'puts', fn (#[NullTerminated] string $message) => print($message));

// (param i32) - pointer to a little-endian u32 byte length, followed by the string
$module->setImport('env', 'print', fn (#[LengthPrefixed] string $message) => print($message));

// (param i32) - pointer to a null-terminated UTF-16 string, converted to UTF-8
$module->setImport('env', 'wputs', fn (#[NullTerminated, Encoding('UTF-16LE')] string $message) => print($message));
```

`#[Encoding]` accepts any encoding supported by `mbstring`. Strings that aren't valid in their encoding, or that extend outside of memory, throw an exception. Strings can be mixed freely with other parameters, and each one consumes as many arguments as its layout needs.

Other layouts can be supported by writing an attribute that implements `Oatmael\WasmPhp\Import\StringDecoder`.

## Implementation Status
| Status | Meaning |
|:--------:|---------|
| ✅ | Fully implemented |
| 🚧 | Partially implemented, WIP |
| ❌ | Not implemented |
| 🚫 | Will not be implemented, or not applicable |

### Instructions
Every instruction in the WebAssembly 3.0 specification is implemented, and the runtime passes the official [spec testsuite](https://github.com/WebAssembly/spec/tree/main/test/core) (apart from the tests that need validation, see [Limitations](#modules-are-not-validated)).

| Instructions | Status | Notes |
|----------|:--------:|--------|
| Control | ✅ | Including tail calls, typed function references and exception handling |
| Reference | ✅ | |
| Variable | ✅ | |
| Table | ✅ | |
| Memory | ✅ | Loads and stores are covered by their value type |
| `i32` | ✅ | |
| `i64` | ✅ | |
| `f32` | ✅ | |
| `f64` | ✅ | |
| Saturating truncation, bulk memory & table (`0xFC` prefix) | ✅ | |
| SIMD and relaxed SIMD (`0xFD` prefix) | ✅ | Relaxed instructions use one of their permitted results deterministically, mostly their non-relaxed equivalent |
| Garbage collection (`0xFB` prefix) | ✅ | |
| Legacy exception handling (`try`, `catch`, `rethrow`, `delegate`) | 🚫 | Superseded by `try_table` and `throw_ref`, and not part of the specification |

### Standardized
These proposals have finished, and make up the current WebAssembly specification. See [finished proposals](https://github.com/WebAssembly/proposals/blob/main/finished-proposals.md).

| Proposal | Spec | Status | Notes |
|----------|:----:|:--------:|--------|
| [MVP](https://github.com/WebAssembly/design/blob/main/MVP.md) | 1.0 | ✅ | See instructions above |
| [Import/Export of Mutable Globals](https://github.com/WebAssembly/mutable-global) | 1.0 | ✅ | |
| [Non-trapping float-to-int conversions](https://github.com/WebAssembly/nontrapping-float-to-int-conversions) | 2.0 | ✅ | |
| [Sign-extension operators](https://github.com/WebAssembly/sign-extension-ops) | 2.0 | ✅ | |
| [Multi-value](https://github.com/WebAssembly/multi-value) | 2.0 | ✅ | |
| [JavaScript BigInt to WebAssembly i64 integration](https://github.com/WebAssembly/JS-BigInt-integration) | 2.0 | 🚫 | JavaScript-specific. Imports can use `BigInteger` instead |
| [Reference Types](https://github.com/WebAssembly/reference-types) | 2.0 | ✅ | |
| [Bulk memory operations](https://github.com/WebAssembly/bulk-memory-operations) | 2.0 | ✅ | |
| [Fixed-width SIMD](https://github.com/webassembly/simd) | 2.0 | ✅ | |
| [Tail call](https://github.com/WebAssembly/tail-call) | 3.0 | ✅ | |
| [Extended Constant Expressions](https://github.com/WebAssembly/extended-const) | 3.0 | ✅ | |
| [Typed Function References](https://github.com/WebAssembly/function-references) | 3.0 | ✅ | |
| [Garbage collection](https://github.com/WebAssembly/gc) | 3.0 | ✅ | |
| [Multiple memories](https://github.com/WebAssembly/multi-memory) | 3.0 | ✅ | |
| [Relaxed SIMD](https://github.com/WebAssembly/relaxed-simd) | 3.0 | ✅ | Deterministic, see instructions above |
| [Custom Annotation Syntax in the Text Format](https://github.com/WebAssembly/annotations) | 3.0 | 🚫 | Text format only |
| [Branch Hinting](https://github.com/WebAssembly/branch-hinting) | 3.0 | 🚫 | Hints are stored in a custom section, which is ignored |
| [Exception handling](https://github.com/WebAssembly/exception-handling) | 3.0 | ✅ | The legacy instructions aren't supported |
| [JS String Builtins](https://github.com/WebAssembly/js-string-builtins) | 3.0 | 🚫 | JavaScript-specific |
| [Memory64](https://github.com/WebAssembly/memory64) | 3.0 | ✅ | Including 64-bit tables |

### In progress
These proposals are still moving through the standardization process. See [proposals](https://github.com/WebAssembly/proposals).

#### Phase 5 - Standardized
| Proposal | Status | Notes |
|----------|:--------:|--------|
| [JS Promise Integration](https://github.com/WebAssembly/js-promise-integration) | 🚫 | JavaScript-specific |
| [Web Content Security Policy](https://github.com/WebAssembly/content-security-policy) | 🚫 | Web-specific |

#### Phase 4 - Standardization
| Proposal | Status | Notes |
|----------|:--------:|--------|
| [Threads](https://github.com/webassembly/threads) | ❌ | Will only emulate threads |
| [Compact Import Section](https://github.com/WebAssembly/compact-import-section) | ❌ | |
| [Wide Arithmetic](https://github.com/WebAssembly/wide-arithmetic) | ❌ | |

#### Phase 3 - Implementation
| Proposal | Status | Notes |
|----------|:--------:|--------|
| [ESM Integration](https://github.com/WebAssembly/esm-integration) | 🚫 | JavaScript-specific |
| [Stack Switching](https://github.com/WebAssembly/stack-switching) | ❌ | |
| [Custom Page Sizes](https://github.com/WebAssembly/custom-page-sizes) | ❌ | |
| [Custom Descriptors and JS Interop](https://github.com/WebAssembly/custom-descriptors) | 🚫 | JavaScript-specific |

#### Phase 2 - Specification
| Proposal | Status | Notes |
|----------|:--------:|--------|
| [Relaxed dead code validation](https://github.com/WebAssembly/relaxed-dead-code-validation) | 🚫 | Modules are not validated |
| [Numeric Values in WAT Data Segments](https://github.com/WebAssembly/wat-numeric-values) | 🚫 | Text format only |
| [Extended Name Section](https://github.com/WebAssembly/extended-name-section) | ❌ | |
| [Rounding Variants](https://github.com/WebAssembly/rounding-mode-control) | ❌ | |
| [Compilation Hints](https://github.com/WebAssembly/compilation-hints) | 🚫 | Hints are stored in a custom section, which is ignored |
| [JS Primitive Builtins](https://github.com/WebAssembly/js-primitive-builtins) | 🚫 | JavaScript-specific |
| [Acquire-Release Atomics](https://github.com/WebAssembly/acquire-release-atomics) | ❌ | |
| [Multibyte Array Access](https://github.com/WebAssembly/multibyte-array-access) | ❌ | |
| [FP16](https://github.com/WebAssembly/half-precision) | ❌ | |

#### Phase 1 - Proposal
| Proposal | Status | Notes |
|----------|:--------:|--------|
| [Type Imports](https://github.com/WebAssembly/proposal-type-imports) | ❌ | |
| [Component Model](https://github.com/WebAssembly/component-model) | ❌ | |
| [WebAssembly C and C++ API](https://github.com/WebAssembly/wasm-c-api) | 🚫 | C/C++ embedding API |
| [Flexible Vectors](https://github.com/WebAssembly/flexible-vectors) | ❌ | |
| [Memory control](https://github.com/WebAssembly/memory-control) | ❌ | |
| [Reference-Typed Strings](https://github.com/WebAssembly/stringref) | ❌ | |
| [Profiles](https://github.com/WebAssembly/profiles) | ❌ | |
| [Shared-Everything Threads](https://github.com/WebAssembly/shared-everything-threads) | ❌ | |
| [Frozen Values](https://github.com/WebAssembly/frozen-values) | ❌ | |
| [More Array Constructors](https://github.com/WebAssembly/more-array-constructors/) | ❌ | |
| [JIT Interface](https://github.com/WebAssembly/jit-interface) | ❌ | |
| [Type Reflection for WebAssembly JavaScript API](https://github.com/WebAssembly/js-types) | 🚫 | JavaScript-specific |
| [JS Text Encoding Builtins](https://github.com/WebAssembly/js-text-encoding-builtins) | 🚫 | JavaScript-specific |

## Why run WASM inside PHP?
WASM has proven itself incredibly useful for running sandboxed, optimised code.

Most often, this means running near-native code in the browser, or on the server with runtimes like Node.js.

However, the sandboxed nature of WASM also allows for more targeted use cases - such as running untrusted code, which opens up the opportunity to execute user-submitted logic on the server.

This is where PHP comes in - it may not be the flashiest tool in the box, but if [W3Techs surveys](https://w3techs.com/technologies/overview/programming_language) are to be trusted, it takes up the lion's share of the web.

There are already ways to execute WASM in PHP, but the vast majority of them either have limited support, or reach out to external runtimes to execute modules. This comes with a variety of limitations - FFI can be unwieldy and mask errors, extensions can be cumbersome to install and support, some runtimes don't support every architecture, and shelling out to an executable is not ideal.

Running the entire runtime within PHP not only solves these issues, but also allows additional features and abstractions to be built on top of it - such as host functions that receive typed PHP values and strings, rather than raw stack values.

## Limitations
PHP is not designed for the things WASM is designed to do, and running WASM inside it comes with some inherent downsides:

### PHP integers are always signed
WASM works with the number types `i32`, `i64`, `f32` and `f64`, and many of its operations treat integers as either signed or unsigned (such as `i32.div_s` and `i32.div_u`). PHP only has signed 64-bit integers, and integer arithmetic that overflows silently turns into a float.

`i32` values fit comfortably within a PHP integer, which is why 64-bit PHP is required. Unsigned `i64` values and overflowing `i64` arithmetic don't fit, so the runtime falls back to arbitrary precision arithmetic with `brick/math` where it needs to. Unsigned `i64` values that are passed to host functions as an `int` would wrap around, so they must be received as a `BigInteger`.

### PHP only has 64-bit floats
`f32` values are stored as PHP floats, rounded to single precision when they're created, so results match native single precision arithmetic. Values loaded from memory or reinterpreted from an `i32` keep their exact bits, so NaN payloads survive being moved around and through the sign operations (`abs`, `neg` and `copysign`), but arithmetic on a NaN produces a quiet NaN, as wasm allows.

### Modules are not validated
The runtime assumes that modules are valid, as produced by a compiler or `wasm-tools`. An invalid module may fail with an unhelpful error, or behave unpredictably, rather than being rejected when it is loaded. This is why the spec tests' `assert_invalid` and `assert_malformed` commands are skipped.

### Memory is limited by PHP
Memories are PHP strings and tables are PHP arrays, so they're limited by PHP's `memory_limit`. Growing a memory that would exhaust it fails (`memory.grow` returns -1) rather than crashing, as wasm allows.

### PHP is not optimised for low-level workloads
One of WASM's use cases is running highly performant code outside the limitations of its host language. Since this runtime runs entirely within PHP, it inherits all of the language's limitations - chief among them that PHP isn't built for heavy low-level computation. It was built to handle web requests, and it's great at that, but when it comes to executing raw instructions, don't expect it to win any awards.

This runtime is not currently optimised for speed, and it may never be. It will not beat competing libraries on benchmarks, nor should it be expected to.

## Development
The repository includes a [Nix](https://nixos.org) flake with PHP 8.5, Composer and [wasm-tools](https://github.com/bytecodealliance/wasm-tools):

```bash
nix develop        # or `direnv allow`, using the included .envrc
composer install
./vendor/bin/pest
```

Without Nix, install PHP 8.5, Composer and wasm-tools yourself. Most tests compile inline WAT, so they need `wasm-tools` on your `PATH` (or set the `WASM_TOOLS` environment variable to its location).

The official spec tests run separately, as they take about a minute:

```bash
composer spec:fetch   # download the testsuite into tests/Spec/testsuite, pinned to a known commit
composer spec         # run all of it, or: php tests/Spec/run.php -v tests/Spec/testsuite/test/core/i32.wast
```

Each instruction is a class in `src/Instruction`, named after its opcode (`i32.load8_s` is `I32Load8S`). Prefixed opcodes live in the `Gc` (`0xFB`), `Extension` (`0xFC`) and `Simd` (`0xFD`) subdirectories.
