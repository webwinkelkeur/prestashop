#!/bin/bash

set -euo pipefail
cd "$(dirname "$0")"

cpus=$(nproc)
threads=$((cpus+1))

mkdir -p log

test() {
    if ./run.sh --headless=false $1 >log/$1.log 2>&1; then
        echo "PASS $1"
        rm -f log/$1.log
    else
        echo "FAIL $1"
    fi
}
export -f test

xargs -P $threads -n 1 bash -c 'test "$0"' < versions
