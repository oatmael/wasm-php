# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.
Allows follow DRY, YAGNI & KISS principles. YAGNI takes precedent over DRY - never abstract prematurely.

## Project

A WebAssembly runtime written in pure PHP (requires PHP ^8.5). Namespace `Oatmael\WasmPhp\` maps to `src/` (PSR-4). Depends on `brick/math` for arbitrary-precision arithmetic. Still a WIP; see the proposal status table in README.md.

## Commands

```bash
nix develop                         # dev shell with PHP 8.5, composer and wasm-tools (or `direnv allow` with the included .envrc)
composer install
./vendor/bin/pest                   # run all tests
./vendor/bin/pest tests/Feature/StandardOpcode/i32Test.php   # one file
./vendor/bin/pest --filter=i32_add  # one test by name
composer spec:fetch                 # download the official spec testsuite (pinned commit) into tests/Spec/testsuite
composer spec                       # run it (~1 min); or php tests/Spec/run.php -v <file.wast|dir>
```

Tests need `wasm-tools` on `PATH` (override with the `WASM_TOOLS` env var), because most tests compile inline WAT at runtime (`wasm-tools parse`; WABT can't parse the GC text format). CI (`.github/workflows/test.yml`) runs Pest and, in a separate job, the spec testsuite on PHP 8.5.

## Architecture

**Decoding (`src/Util/WasmReader.php`)**: `WasmReader::read()` parses the raw binary string; all offsets are byte offsets. The static helpers (`readUint8`, `readLEB128Uint32/Uint64`, `readLEB128Int32/Int64`, `readValueType`, `readHeapType`, `readBlockType`, `readMemArg`, etc.) take `(string $input, int &$offset)` and advance the offset. Every Wasm 3.0 section form is decoded: rec groups and subtypes (into `Func`/`StructType`/`ArrayType` plus `[start, size]` rec groups), all import/export kinds, tables with initialisers, the 8 element segment forms and passive/active data (`SegmentMode`), and tags. Constant expressions are kept as instruction lists and evaluated at instantiation (`Store::evaluate`). Value types are `ValueType` (numbers, v128) or `RefType` (nullable + `HeapType` or a module-relative type index). Legacy exception handling and shared memories/custom page sizes throw.

**Opcode dispatch (`WasmReader::readInstruction()`)**: the enums `StandardOpcode`, `GcOpcode` (`0xfb`, subnamespace `Instruction\Gc`), `ExtensionOpcode` (`0xfc`, `Instruction\Extension`) and `SimdOpcode` (`0xfd`, `Instruction\Simd`) live in `src/Instruction/`. Prefixed opcodes are read as a LEB128 u32 after the prefix byte. The instruction class is derived from the enum case name (`i32_load8_s` → `I32Load8S`), except reserved words in `RESERVED_INSTRUCTION_NAMES` (`if` → `IIf`, `else` → `IElse`, `return` → `IReturn`, `throw` → `IThrow`). Every opcode in the 3.0 spec is implemented.

**Instruction contract (`InstructionInterface`)**:
- `static fromInput(string $input, int &$offset)` decodes any immediates (with `WasmReader` helpers) and returns an instance.
- Memory immediates use `WasmReader::readMemArg()` → `[align, memory_idx, offset]` (bit 6 of align flags a memory index; the offset is a u64 that wraps negative above 2^63). Loads/stores go through `Memory::load()`/`store()`, which handle i32/i64 (memory64) addresses and bounds traps.
- `execute(array &$stack, array &$call_stack, Store $store)` changes the operand stack and call stack in place. Operands are popped in reverse order (`$right` first). Modules are assumed valid, so new code doesn't re-check operand types.
- Traps throw `TrapException` (or a subclass) with the spec's wording at the start of the message; the spec runner matches on it.

**Execution (`src/Execution/Store.php`)**: a `Store` is a module instance. `instantiate()` (run on first use) resolves imports, creates function instances, tags, globals (in order), memories and tables, evaluates segments, applies active segments and runs the start function. `invoke()` runs a flat loop (`Store::run`) over the top frame; no PHP recursion is used for wasm calls. Each `Frame` carries the `Store` of the function's defining module, so cross-module calls, tables and imports just work. `MAX_CALL_STACK` (on `Module`) is 10,000. `pushFrame` / `popFrame` / `tailCall` / `branch` manage frames and labels; `ControlStackEntry` holds a label's break target, stack height and arity (plus catch clauses for `try_table`). `throw` raises `UncaughtException`; `run()` catches it and `Store::catch()` unwinds to the innermost matching `try_table`, otherwise it escapes to PHP. `Module` is a thin facade (`execute`, `setImport`, `getExport`, `getImports`, `getMemory`, `instantiate`).

**Types (`src/Execution/TypeRegistry.php`)**: types are canonicalised iso-recursively into global ids (`Store::$canonical[type idx]`), so equivalent types from different modules compare equal. `Store::refMatches()` / `TypeRegistry::heapMatches()` implement ref subtyping for casts, `call_indirect` and import checks. Runtime `Table`/`GlobalVariable` types hold canonical ids in place of type indices.

**Host imports (`src/Import/`)**: `setImport($module, $field, $value)` takes a PHP callable (wrapped in a `HostFunction`) or a `FuncRef`/`Table`/`Memory`/`GlobalVariable`/`Tag` (e.g. another module's `getExport()`), type-checked against the import (`LinkException` on mismatch). `HostFunction` uses reflection once at registration to bind each PHP parameter to a slice of the wasm parameters. `int`/`float`/`bool` and the value wrappers take one argument each (`#[Unsigned] int` for an unsigned i32; `BigInteger`, optionally `#[Unsigned]`, for an i64; untyped/`mixed`/`ValueInterface` gets the raw wrapper, which is the only option for references). `Memory` and `Store` parameters are injected. A `string` takes the i32 arguments its `StringDecoder` attribute asks for (`PointerLength` default, `NullTerminated`, `LengthPrefixed`), read from memory 0 and decoded from `#[Encoding(...)]` (default UTF-8). Return values are converted to the wasm result types (an array for multiple results; ignored for void imports; `null` for nullable refs).

**Values (`src/Type/`)**: `I32`/`I64`/`F32`/`F64`/`V128` wrap PHP values. `I32` stores an int and normalises it when read (`getValue()` sign-extends, `toUnsigned()` gives the unsigned view). Overflowing/unsigned i64 math uses `brick/math` (`I64::toBigInteger()`, `fromBigInteger()`, and wrapping `I64::add/sub/mul`). `F32` rounds to single precision on construction and `fromBits()` keeps exact bits (NaN payloads); use `getBits()` for sign/bit ops. `V128` is 16 bytes, split with `lanes(Shape, signed)` / `fromLanes()`. References: `NullRef`, `FuncRef` (also the function instance), `ExternRef`, `HostRef` (internalised extern), `I31Ref`, `StructRef`, `ArrayRef`, `ExnRef`. `Util\Floats` holds wasm float semantics shared by scalar and SIMD ops (min/max, nearest, trapping/saturating truncation, correctly rounded int→float). `Memory::$data` is a binary string.

## Tests

Pest. Feature tests are grouped as `tests/Feature/{StandardOpcode,GcOpcode,ExtensionOpcode,SimdOpcode}/<type>Test.php`, one `test()` per opcode. `wat2module()`, `compileWat()`, `wasmOp()` (run one instruction over typed args), `v128()` and `simdOp()` are global helpers in `tests/Pest.php`. Assert on `$ret[0]->getValue()`; results come back as an array. `tests/Spec/` holds the spec testsuite runner (`SpecRunner.php`, `run.php`, `fetch-testsuite.sh`), which uses `wasm-tools json-from-wast` and skips `assert_invalid`/`assert_malformed`.
