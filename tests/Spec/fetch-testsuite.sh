#!/usr/bin/env bash
# Downloads the official spec tests into tests/Spec/testsuite, pinned to a known commit of WebAssembly/spec
set -euo pipefail

COMMIT=aa4dcf44b8de3f3f3270ca067422172518ecc341
DIR="$(dirname "$0")/testsuite"

rm -rf "$DIR"
git init -q "$DIR"
git -C "$DIR" remote add origin https://github.com/WebAssembly/spec.git
git -C "$DIR" sparse-checkout set test/core
git -C "$DIR" fetch -q --depth 1 origin "$COMMIT"
git -C "$DIR" checkout -q FETCH_HEAD
echo "Spec tests at $COMMIT downloaded to $DIR"
