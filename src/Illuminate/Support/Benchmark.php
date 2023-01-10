<?php

namespace Illuminate\Support;

use Closure;

class Benchmark
{
    /**
     * Measure a callable or array of callables over the given number of iterations.
     *
     * @param  \Closure|array  $benchmarkables
     * @param  int  $iterations
     * @return array|float
     */
    public static function measure(Closure|array $benchmarkables, int $iterations = 1): array|float
    {
        return collect(Arr::wrap($benchmarkables))->map(function ($callback) use ($iterations) {
            return collect(range(1, $iterations))->map(function () use ($callback) {
                gc_collect_cycles();

                $start = hrtime(true);

                $callback();

                return (hrtime(true) - $start) / 1000000;
            })->average();
        })->when(
            $benchmarkables instanceof Closure,
            fn ($c) => $c->first(),
            fn ($c) => $c->all(),
        );
    }

    /**
     * Measure a callable or array of callables over the given number of iterations, then dump and die.
     *
     * @param  \Closure|array  $benchmarkables
     * @param  int  $iterations
     * @return never
     */
    public static function dd(Closure|array $benchmarkables, int $iterations = 1): void
    {
        $result = collect(static::measure(Arr::wrap($benchmarkables), $iterations))
            ->map(fn ($average) => number_format($average, 3).'ms')
            ->when($benchmarkables instanceof Closure, fn ($c) => $c->first(), fn ($c) => $c->all());

        dd($result);
    }
    
    
    /**
     * Measure elapsed time of a callback, then dump and die.
     *
     * @param  \Closure $callback
     * @param  int  $iterations
     * @return never
     */
    public static function elapsed(Closure $callback, int $iterations = 1): void
    {
        $start = hrtime(true);

        for ($i = 0; $i < $iterations; ++$i) {
            $callback();
        }
        
        $end = hrtime(true);

        $eta = $end - $start;

        $eta /= 1e+6;

        dd($eta);
    }
}
