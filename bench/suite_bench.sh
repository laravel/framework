#!/usr/bin/env bash
# PHPUnit-suite comparison: bench/baseline (docblock collections) vs bench/reified
# (native reified generics) on the SAME fork binary. Switches by git branch,
# flushes the persistent opcache SHM between branches, runs a warmup (also a
# green-gate) + N timed runs per (branch, workload), reports median/min/spread.
#
# NOTE: the suite is collection-RICH by test count but harness-BOUND by wall-clock
# (~0.45s/process startup dominates), so it under-resolves collection throughput.
# Use bench/perfbench.sh (instruction count) for the real cost signal.
#
#   [PHP=/path/to/php] [RUNS=11] bench/suite_bench.sh
set -uo pipefail
REPO="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PHP="${PHP:-/home/withinboredom/code/php-src/sapi/cli/php}"
FLAGS=(-d opcache.enable_cli=1 -d opcache.jit=disable -d opcache.jit_buffer_size=0 -d opcache.validate_timestamps=1)
RUNS="${RUNS:-11}"
PHPUNIT_COMMON=(--do-not-cache-result --order-by=default --no-progress)
cd "$REPO" || exit 1

# Workloads: name|paths. tests/Pagination is excluded -- it loads out-of-scope
# Eloquent\Collection, whose untyped collapse() is incompatible with the typed
# parent under reification.
WORKLOADS=(
  "support|tests/Support"
  "collections|tests/Support/SupportCollectionTest.php tests/Support/SupportLazyCollectionTest.php tests/Support/SupportLazyCollectionIsLazyTest.php"
)
[ "${DRY:-0}" = "1" ] && { WORKLOADS=("collections|tests/Support/SupportCollectionTest.php"); RUNS=1; }

median() { sort -n | awk '{a[NR]=$1} END{ if(NR%2){print a[(NR+1)/2]} else {printf "%.4f\n",(a[NR/2]+a[NR/2+1])/2} }'; }
stat()   { sort -n | awk '{a[NR]=$1; s+=$1} END{ printf "median=%.3fs  min=%.3fs  max=%.3fs  mean=%.3fs  n=%d", (NR%2?a[(NR+1)/2]:(a[NR/2]+a[NR/2+1])/2), a[1], a[NR], s/NR, NR }'; }

run_config() {
  local branch="$1" wname="$2"; shift 2
  local paths=("$@")
  local warm; warm="$("$PHP" "${FLAGS[@]}" vendor/bin/phpunit "${paths[@]}" "${PHPUNIT_COMMON[@]}" 2>&1)"
  local summary; summary="$(printf '%s\n' "$warm" | grep -E 'OK \(|Tests: |ERRORS|FAILURES|Fatal error' | tr '\n' ' ')"
  if ! printf '%s' "$warm" | grep -qE 'OK|OK, but'; then
    echo "  !! $branch/$wname WARMUP NOT GREEN: $summary" >&2
    return 1
  fi
  echo "  warmup green -> $summary" >&2
  local times=()
  for i in $(seq 1 "$RUNS"); do
    local s e
    s="$(date +%s.%N)"
    "$PHP" "${FLAGS[@]}" vendor/bin/phpunit "${paths[@]}" "${PHPUNIT_COMMON[@]}" >/dev/null 2>&1
    e="$(date +%s.%N)"
    times+=("$(awk -v a="$s" -v b="$e" 'BEGIN{printf "%.4f", b-a}')")
  done
  printf '%s\n' "${times[@]}" > "/tmp/bench_${branch//\//_}_${wname}.txt"
  echo "  $(printf '%s\n' "${times[@]}" | stat)" >&2
  printf '%s\n' "${times[@]}" | median
}

declare -A RESULT
for branch in bench/baseline bench/reified; do
  echo "============================================================"
  echo "BRANCH: $branch"
  git checkout -q "$branch" || { echo "checkout failed"; exit 1; }
  echo "  HEAD $(git rev-parse --short HEAD)"
  "$PHP" "${FLAGS[@]}" -r 'opcache_reset();' >/dev/null 2>&1
  for w in "${WORKLOADS[@]}"; do
    wname="${w%%|*}"; pathstr="${w#*|}"; read -r -a paths <<< "$pathstr"
    echo "-- workload: $wname (${paths[*]})"
    m="$(run_config "$branch" "$wname" "${paths[@]}")" || { echo "ABORT"; git checkout -q bench/reified; exit 1; }
    RESULT["$branch|$wname"]="$m"
  done
done

git checkout -q bench/reified
echo "============================================================"
echo "MEDIANS (seconds, lower = faster):"
for w in "${WORKLOADS[@]}"; do
  wname="${w%%|*}"
  b="${RESULT[bench/baseline|$wname]}"; r="${RESULT[bench/reified|$wname]}"
  ratio="$(awk -v b="$b" -v r="$r" 'BEGIN{ if(b>0) printf "%.1f%%", (r-b)/b*100; else print "n/a" }')"
  printf "  %-12s baseline=%ss  reified=%ss  delta=%s\n" "$wname" "$b" "$r" "$ratio"
done
echo "(restored working tree to bench/reified)"
