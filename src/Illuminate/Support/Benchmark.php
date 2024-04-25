<?php

namespace Illuminate\Support;

use Closure;
use InvalidArgumentException;

class Benchmark
{
    /**
     * Measure a callable or array of callables over the given number of iterations.
     *
     * @param  \Closure|array  $benchmarkables
     * @param  int  $iterations
     * @return array|float
     */
    public static function measure(Closure|array $benchmarkables, int $iterations = 1, string|array $aggregateFunctions = 'average'): array|float
    {
        return collect(Arr::wrap($benchmarkables))->map(function ($callback) use ($aggregateFunctions, $iterations) {
            $timings =  collect(range(1, $iterations))->map(function () use ($callback) {
                gc_collect_cycles();

                $start = hrtime(true);

                $callback();

                return (hrtime(true) - $start) / 1000000;
            });

            return self::aggregateTimings($timings, $aggregateFunctions);
        })->when(
            $benchmarkables instanceof Closure,
            fn ($c) => $c->first(),
            fn ($c) => $c->all(),
        );
    }

    /**
     * Measure a callable once and return the duration and result.
     *
     * @template TReturn of mixed
     *
     * @param  (callable(): TReturn)  $callback
     * @return array{0: TReturn, 1: float}
     */
    public static function value(callable $callback): array
    {
        gc_collect_cycles();

        $start = hrtime(true);

        $result = $callback();

        return [$result, (hrtime(true) - $start) / 1000000];
    }

    /**
     * Measure a callable or array of callables over the given number of iterations, then dump and die.
     *
     * @param  \Closure|array  $benchmarkables
     * @param  int  $iterations
     * @return never
     */
    public static function dd(Closure|array $benchmarkables, int $iterations = 1, string|array $aggregateFunctions = 'average'): void
    {
        $result = collect(static::measure(Arr::wrap($benchmarkables), $iterations, $aggregateFunctions))
            ->map(fn ($average) => number_format($average, 3).'ms')
            ->when($benchmarkables instanceof Closure, fn ($c) => $c->first(), fn ($c) => $c->all());

        dd($result);
    }

    /**
     * Calculate the aggregate of the given timings based on the provided aggregate functions.
     *
     * Supported aggregate functions: average, mean, sum, total, min, max, median, p*, all
     *
     * @param Collection $timings
     * @param string|array $aggregateFunctions
     * @return mixed
     */
    protected static function aggregateTimings(Collection $timings, string|array $aggregateFunctions): mixed
    {
        $aggregateFunctions = Arr::wrap($aggregateFunctions);

        $aggregateResult = [];

        foreach ($aggregateFunctions as $aggregateFunction) {
            // Match p00 - p100 (e.g. p95 or p50) to return percentiles
            if (preg_match('/^p(\d+)$/', $aggregateFunction, $matches)) {
                $aggregateResult[$aggregateFunction] = self::percentile($timings, $matches[1]);
            } else {
                $aggregateResult[$aggregateFunction] = match($aggregateFunction) {
                    'average', 'mean' => $timings->average(),
                    'sum', 'total' => $timings->sum(),
                    'min' => $timings->min(),
                    'max' => $timings->max(),
                    'median' => self::percentile($timings, 50),
                    'all' => $timings->all(),
                    default => throw new InvalidArgumentException("Unsupported benchmark aggregate function: $aggregateFunction"),
                };
            }
        }

        return count($aggregateFunctions) > 1 ? $aggregateResult : head($aggregateResult);
    }

    /**
     * Returns the percentile value of the given timings.
     *
     * @param Collection $timings
     * @param int $percentile
     * @return float
     */
    protected static function percentile(Collection $timings, int $percentile): float
    {
        return $timings->sort()->values()->get((int) (($timings->count() - 1) * ($percentile / 100)));
    }
}
