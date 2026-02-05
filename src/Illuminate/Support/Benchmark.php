<?php

namespace Illuminate\Support;

use Closure;
use Illuminate\Support\Traits\Macroable;

class Benchmark
{
    use Macroable;

    /**
     * Measure a callable or array of callables over the given number of iterations.
     *
     * @param  \Closure|array  $benchmarkables
     * @param  int  $iterations
     * @return array|float
     */
    public static function measure(Closure|array $benchmarkables, int $iterations = 1): array|float
    {
        return Collection::wrap($benchmarkables)->map(function ($callback) use ($iterations) {
            return Collection::range(1, $iterations)->map(function () use ($callback) {
                gc_collect_cycles();

                $start = hrtime(true);

                $callback();

                return (hrtime(true) - $start) / 1_000_000;
            })->average();
        })->when(
            $benchmarkables instanceof Closure,
            fn ($c) => $c->first(),
            fn ($c) => $c->all(),
        );
    }

    /**
     * Measure a callable once and return the result and duration in milliseconds.
     *
     * @template TReturn of mixed
     *
     * @param  (callable(): TReturn)  $callback
     * @return array{0: TReturn, 1: float}
     */
    public static function value(callable $callback): array
    {
        return static::timed($callback);
    }

    /**
     * Measure a callable once and return the result and duration in the provided time unit.
     * If the callable throws an exception, it will be re-thrown.
     *
     * @template TReturn of mixed
     *
     * @param  (callable(): TReturn)  $callback
     * @param  BenchmarkTimeUnit  $unit
     * @return array{0: TReturn, 1: float}
     *
     * @example
     * [$result, $duration] = Benchmark::timed(fn() => sleep(1), BenchmarkTimeUnit::Seconds);
     * // Returns: [null, 1.0]
     */
    public static function timed(callable $callback, BenchmarkTimeUnit $unit = BenchmarkTimeUnit::Milliseconds): array
    {
        gc_collect_cycles();

        $start = hrtime(true);
        $result = $callback();
        $duration = hrtime(true) - $start;

        return [$result, $duration / $unit->divisor()];
    }

    /**
     * Measure a callable once and return the result and duration in the provided time unit.
     * If the callable throws an exception, it will be caught and returned.
     *
     * @template TReturn of mixed
     *
     * @param  (callable(): TReturn)  $callback
     * @param  BenchmarkTimeUnit  $unit
     * @return array{
     *     0: TReturn|null,
     *     1: float,
     *     2: \Throwable|null
     * }
     *
     * @example
     * [$result, $duration, $exception] = Benchmark::tryTimed(
     *     fn() => throw new RuntimeException('Error'),
     *     BenchmarkTimeUnit::Milliseconds
     * );
     * // Returns: [null, 0.123, RuntimeException]
     */
    public static function tryTimed(callable $callback, BenchmarkTimeUnit $unit = BenchmarkTimeUnit::Milliseconds): array
    {
        gc_collect_cycles();

        $start = hrtime(true);
        $result = null;
        $exception = null;

        try {
            $result = $callback();
        } catch (\Throwable $e) {
            $exception = $e;
        }

        $duration = hrtime(true) - $start;

        return [$result, $duration / $unit->divisor(), $exception];
    }

    /**
     * Measure a callable or array of callables over the given number of iterations, then dump and die.
     *
     * @param  \Closure|array  $benchmarkables
     * @param  int  $iterations
     * @return never
     */
    public static function dd(Closure|array $benchmarkables, int $iterations = 1): never
    {
        $result = (new Collection(static::measure(Arr::wrap($benchmarkables), $iterations)))
            ->map(fn ($average) => number_format($average, 3).'ms')
            ->when($benchmarkables instanceof Closure, fn ($c) => $c->first(), fn ($c) => $c->all());

        dd($result);
    }
}
