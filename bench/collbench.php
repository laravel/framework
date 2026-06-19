<?php
// Collection-CPU micro-benchmark: a tight loop where collection operations
// dominate wall-clock (unlike the PHPUnit suite, which is ~0.45s/process
// startup-bound). Run the SAME script on bench/baseline (docblock generics)
// vs bench/reified (native reified generics) to isolate reification cost.
//
//   php -d opcache.enable_cli=1 -d opcache.jit=disable bench/collbench.php [ITERS] [SIZE] [REPS]
//
// Prints class name (carries the "<mixed,mixed>" tell on the reified branch),
// a median/min/max wall-clock line with a checksum (proves identical behavior
// across branches), and a machine-readable MEDIAN_MS= line on stderr.

require __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Collection;

gc_disable();

$ITERS = (int) ($argv[1] ?? 3000);   // pipeline builds per rep
$SIZE  = (int) ($argv[2] ?? 2000);   // elements per collection
$REPS  = (int) ($argv[3] ?? 9);      // measured repetitions (median)

$data = [];
for ($i = 0; $i < $SIZE; $i++) {
    $data[] = ['id' => $i, 'v' => ($i * 7) % 101, 'g' => $i % 13];
}

// One unit of work: a realistic chain hitting the hot paths
// (map/filter/reduce/groupBy/sort/pluck/keyBy/push/get).
$work = static function () use ($data) {
    $c = new Collection($data);
    $r = $c->map(fn ($row) => $row['v'] * 2)
           ->filter(fn ($v) => $v % 3 !== 0)
           ->values();
    $grouped = $c->groupBy(fn ($row) => $row['g'])
                 ->map(fn (Collection $g) => $g->pluck('v')->sum())
                 ->sortDesc()
                 ->take(5)
                 ->all();
    $keyed = $c->keyBy('id');
    $sum   = $r->reduce(fn ($carry, $v) => $carry + $v, 0);
    $acc = new Collection();
    for ($k = 0; $k < 50; $k++) {
        $acc->push($k);
    }
    return $sum + $acc->get(25) + array_sum($grouped) + ($keyed->get(10)['v'] ?? 0);
};

// warmup
for ($w = 0; $w < 200; $w++) { $work(); }

$samples = [];
for ($rep = 0; $rep < $REPS; $rep++) {
    $t0 = hrtime(true);
    $checksum = 0;
    for ($i = 0; $i < $ITERS; $i++) {
        $checksum += $work();
    }
    $t1 = hrtime(true);
    $samples[] = ($t1 - $t0) / 1e6; // ms
}

sort($samples);
$n = count($samples);
$median = $n % 2 ? $samples[($n - 1) / 2] : ($samples[$n / 2 - 1] + $samples[$n / 2]) / 2;

printf("class=%s  iters=%d size=%d reps=%d\n", (new Collection())::class, $ITERS, $SIZE, $REPS);
printf("median=%.2fms  min=%.2fms  max=%.2fms  (checksum=%d)\n",
    $median, $samples[0], $samples[$n - 1], $checksum);
// machine-readable median for the wrapper:
fwrite(STDERR, sprintf("MEDIAN_MS=%.4f\n", $median));
