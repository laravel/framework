#!/usr/bin/env bash
# Instruction-count comparison: bench/baseline (docblock) vs bench/reified (native
# reified generics), same fork binary, same workload, via perf_event_open
# (bench/perfcount). Instruction counts are frequency-independent -> no AC-power
# or quiet-machine gate, and ~100x more stable than wall-clock.
#
# Build the counter once:  gcc -O2 -o bench/perfcount bench/perfcount.c
# Run:                      [PHP=/path/to/php] bench/perfbench.sh
set -uo pipefail
REPO="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PHP="${PHP:-/home/withinboredom/code/php-src/sapi/cli/php}"
PC="$REPO/bench/perfcount"
FLAGS=(-d opcache.enable_cli=1 -d opcache.jit=disable -d opcache.jit_buffer_size=0 -d opcache.validate_timestamps=1)
ITERS="${ITERS:-1000}"; SIZE="${SIZE:-2000}"; REPS="${REPS:-1}"; N="${N:-5}"
cd "$REPO" || exit 1
[ -x "$PC" ] || { echo "build the counter first: gcc -O2 -o bench/perfcount bench/perfcount.c" >&2; exit 1; }

declare -A INS CYC CHK
for branch in bench/baseline bench/reified; do
  git checkout -q "$branch" || exit 1
  "$PHP" "${FLAGS[@]}" -r 'opcache_reset();' >/dev/null 2>&1
  # prime the opcache SHM with THIS branch's freshly-compiled bytecode
  "$PHP" "${FLAGS[@]}" "$REPO/bench/collbench.php" "$ITERS" "$SIZE" "$REPS" >/dev/null 2>&1
  echo "=== $branch ($(git rev-parse --short HEAD)) ===" >&2
  ins_list=(); cyc_list=()
  for i in $(seq 1 "$N"); do
    err="$("$PC" "$PHP" "${FLAGS[@]}" "$REPO/bench/collbench.php" "$ITERS" "$SIZE" "$REPS" 2>&1 >/tmp/.pcout)"
    ins="$(printf '%s' "$err" | grep -oE 'instructions=[0-9]+' | cut -d= -f2)"
    cyc="$(printf '%s' "$err" | grep -oE 'cycles=[0-9]+' | cut -d= -f2)"
    ins_list+=("$ins"); cyc_list+=("$cyc")
    echo "  run $i: instructions=$ins cycles=$cyc" >&2
  done
  chk="$(grep -oE 'checksum=[0-9]+' /tmp/.pcout | cut -d= -f2)"
  med_ins="$(printf '%s\n' "${ins_list[@]}" | sort -n | awk '{a[NR]=$1} END{print a[int((NR+1)/2)]}')"
  med_cyc="$(printf '%s\n' "${cyc_list[@]}" | sort -n | awk '{a[NR]=$1} END{print a[int((NR+1)/2)]}')"
  INS["$branch"]="$med_ins"; CYC["$branch"]="$med_cyc"; CHK["$branch"]="$chk"
done
git checkout -q bench/reified

echo "============================================================"
b="${INS[bench/baseline]}"; r="${INS[bench/reified]}"
bc_="${CYC[bench/baseline]}"; rc="${CYC[bench/reified]}"
echo "checksum baseline=${CHK[bench/baseline]}  reified=${CHK[bench/reified]}  $([ "${CHK[bench/baseline]}" = "${CHK[bench/reified]}" ] && echo MATCH || echo MISMATCH!!)"
awk -v b="$b" -v r="$r"   'BEGIN{ printf "median instructions  baseline=%d  reified=%d  delta=%+.3f%%\n", b, r, (r-b)/b*100 }'
awk -v b="$bc_" -v r="$rc" 'BEGIN{ printf "median cycles        baseline=%d  reified=%d  delta=%+.3f%%\n", b, r, (r-b)/b*100 }'
echo "(workload: ITERS=$ITERS SIZE=$SIZE REPS=$REPS, N=$N counted runs/branch; restored to bench/reified)"
