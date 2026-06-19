# Reified-generics collection benchmark

Tooling used to measure what **native reified generics cost** when applied to
Laravel's Collections, on a custom PHP fork that implements them
(`class Collection<TKey = mixed, TValue = mixed>`).

Two branches of this repo, run on the **same** fork binary, are compared:

- `bench/baseline` — pristine `13.x` (docblock `@template` generics).
- `bench/reified` — the Collections component (`Collection`, `LazyCollection`,
  `Enumerable`, the `EnumeratesValues` trait, and the `Arrayable` /
  `CanBeEscapedWhenCastToString` contracts) converted to native reified generics.

The conversion touches only the Collections component and the generic contracts
it relies on. Every call site, out-of-scope subclass (Eloquent `Collection`,
paginators, …), and all of `vendor/` are unchanged — defaults on every type
parameter keep them loadable.

## Files

| file | what it does |
|------|--------------|
| `collbench.php`    | Collection-CPU micro-loop (map/filter/reduce/groupBy/sort/keyBy/pluck/push/get). Prints class name, median/min/max ms, and a checksum. |
| `perfcount.c`      | ~90-line `perf_event_open` wrapper. Counts userspace HW events (instructions/cycles/branch-misses/cache-misses) for a child process. No `perf` binary or root needed. |
| `perfbench.sh`     | **Primary.** Runs `collbench.php` on both branches under `perfcount`; reports instruction/cycle deltas + checksum match. Noise-immune. |
| `collbench_run.sh` | Wall-clock cross-check of the micro-loop on both branches. |
| `suite_bench.sh`   | PHPUnit-suite comparison (`tests/Support` + the collection suites). Harness-bound; under-resolves throughput. |

All scripts derive the repo root from their own location and honor a `PHP`
environment variable (default: the fork at
`/home/withinboredom/code/php-src/sapi/cli/php`). They switch branches with
`git checkout` and `opcache_reset()` between branches, then restore
`bench/reified`.

## Why instruction counting (the headline method)

Wall-clock could only ever bound the cost as "lost in noise" (few-percent
jitter ≫ the effect). Instruction-retired counting is **frequency-independent**
(no AC-power / quiet-machine requirement) and ~100× more stable run-to-run, so
it *resolves* a sub-percent cost instead of burying it.

Requirements (met on this WSL2 box):
- PMU exposed to the guest — `ls /sys/devices/cpu/events` lists `instructions`,
  `cycles`, `branch-misses`, `cache-misses`.
- `cat /proc/sys/kernel/perf_event_paranoid` ≤ 2 — allows userspace (`:u`)
  counting without root. `perfcount` sets `exclude_kernel`, so no root is needed.

No distro `perf` package is required (on WSL2 they're pinned to a mismatched
kernel anyway); `perfcount` issues `perf_event_open` directly.

## Running

```sh
# build the counter once
gcc -O2 -o bench/perfcount bench/perfcount.c

# primary: instruction-count comparison (no power gate needed)
bench/perfbench.sh

# wall-clock cross-checks (pin the machine to AC power / quiet first)
bench/collbench_run.sh
bench/suite_bench.sh
```

## Result (2026-06-19)

Workload: 1,200 collection pipeline-builds over 2,000-element collections,
opcache on, JIT off, same fork binary.

| metric | baseline | reified | delta |
|--------|----------|---------|-------|
| instructions | 30,118,386,436 | 30,133,475,229 | **+0.050%** |
| cycles       | 8,820,758,490  | 8,826,092,923  | +0.060% |
| checksum     | 172053000      | 172053000      | **MATCH** |

Within-branch instruction spread was ~10K out of 30.1 **billion** (0.00003%),
so the +0.05% delta is ~4,500× the noise floor — real and tiny (~15.1M extra
instructions total ≈ ~12.6K per pipeline-build: the monomorphization + per-
instance type binding for the Collection objects each pipeline creates). Cycles
track instructions, so those extra instructions aren't stalling — no cache or
branch pathology from reification.

Wall-clock cross-checks agreed within noise (micro-loop +0.1% median; `tests/
Support` −0.2%; collection suites +0.7%), and checksums matched throughout
(identical behavior).

**Conclusion: native reified generics on Collections are essentially free —
+0.05% instructions, behavior identical.**
