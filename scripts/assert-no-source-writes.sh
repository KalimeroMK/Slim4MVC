#!/usr/bin/env bash
#
# Run a command and fail if it created, changed or removed anything under app/,
# or added anything beside the repository.
#
# Both have happened here. Runtime paths were built by counting directory levels
# at the call site and the count was wrong repeatedly: the file queue wrote into
# app/Modules/Core/storage, the autowiring cache into app/storage, and the file
# cache into a storage/ directory next to the checkout rather than inside it.
# app/ is source, so anything appearing there is either a bug or gets committed
# by accident.
#
# Usage: scripts/assert-no-source-writes.sh vendor/bin/phpunit

set -uo pipefail

if [ "$#" -eq 0 ]; then
    echo "usage: $0 <command> [args...]" >&2
    exit 64
fi

root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$root" || exit 1

if command -v sha256sum >/dev/null 2>&1; then
    sha_cmd="sha256sum"
elif command -v shasum >/dev/null 2>&1; then
    sha_cmd="shasum -a 256"
else
    echo "assert-no-source-writes: need sha256sum or shasum" >&2
    exit 69
fi

work="$(mktemp -d)"
trap 'rm -rf "$work"' EXIT

snapshot() {
    # Contents of app/, so a modified file counts too, not just a new one.
    find app -type f -print0 | sort -z | xargs -0 $sha_cmd > "$work/app-$1" 2>/dev/null

    # Names beside the checkout, to catch a path that escaped the project root.
    ls -A .. | sort > "$work/beside-$1"
}

snapshot before
"$@"
status=$?
snapshot after

fail=0

if ! diff -u "$work/app-before" "$work/app-after" > "$work/app.diff"; then
    echo "::error::app/ was modified by: $*"
    echo "app/ is source and must not be written at runtime:"
    tail -n +3 "$work/app.diff" | head -40
    fail=1
fi

if ! diff -u "$work/beside-before" "$work/beside-after" > "$work/beside.diff"; then
    echo "::error::something was written beside the repository by: $*"
    echo "a path escaped the project root:"
    tail -n +3 "$work/beside.diff" | head -40
    fail=1
fi

if [ "$fail" -ne 0 ]; then
    exit 1
fi

exit "$status"
