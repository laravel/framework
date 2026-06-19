#!/usr/bin/env bash
# Wall-clock variant of the collection-CPU micro-loop on bench/baseline vs
# bench/reified (same fork binary). Asserts the checksums MATCH (identical
# behavior) and prints the median-ms delta. Prefer bench/perfbench.sh for a
# noise-immune measurement; this one is the human-readable wall-clock cross-check.
#
#   [PHP=/path/to/php] [ITERS=1200] [SIZE=2000] [REPS=9] bench/collbench_run.sh
set -uo pipefail
REPO="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PHP="${PHP:-/home/withinboredom/code/php-src/sapi/cli/php}"
FLAGS=(-d opcache.enable_cli=1 -d opcache.jit=disable -d opcache.jit_buffer_size=0 -d opcache.validate_timestamps=1)
ITERS="${ITERS:-1200}"; SIZE="${SIZE:-2000}"; REPS="${REPS:-9}"
cd "$REPO" || exit 1

declare -A MED CHK
for branch in bench/baseline bench/reified; do
  git checkout -q "$branch" || exit 1
  "$PHP" "${FLAGS[@]}" -r 'opcache_reset();' >/dev/null 2>&1
  out="$("$PHP" "${FLAGS[@]}" "$REPO/bench/collbench.php" "$ITERS" "$SIZE" "$REPS" 2>/tmp/.collerr)"
  med="$(grep -oE 'MEDIAN_MS=[0-9.]+' /tmp/.collerr | cut -d= -f2)"
  chk="$(printf '%s' "$out" | grep -oE 'checksum=[0-9]+' | cut -d= -f2)"
  echo "=== $branch ($(git rev-parse --short HEAD)) ==="
  printf '%s\n' "$out"
  MED["$branch"]="$med"; CHK["$branch"]="$chk"
done
git checkout -q bench/reified

echo "============================================================"
b="${MED[bench/baseline]}"; r="${MED[bench/reified]}"
echo "checksum baseline=${CHK[bench/baseline]}  reified=${CHK[bench/reified]}  $([ "${CHK[bench/baseline]}" = "${CHK[bench/reified]}" ] && echo MATCH || echo MISMATCH!!)"
awk -v b="$b" -v r="$r" 'BEGIN{ printf "median ms  baseline=%.1f  reified=%.1f  delta=%+.1f%%\n", b, r, (r-b)/b*100 }'
echo "(restored to bench/reified)"
