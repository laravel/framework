<?php

namespace Illuminate\Foundation\Benchmark;

use Illuminate\Support\Arr;
use InvalidArgumentException;
use Laravel\SerializableClosure\Support\ReflectionClosure;

class PendingBenchmark
{
    /**
     * The benchmark renderer.
     *
     * @var \Illuminate\Contracts\Foundation\BenchmarkRenderer
     */
    protected $renderer;

    /**
     * The number of repeats.
     *
     * @var int
     */
    protected $repeat = 10;

    /**
     * Creates a new "pending" Benchmark instance.
     *
     * @param  \Illuminate\Contracts\Foundation\BenchmarkRenderer  $renderer
     * @return void
     */
    public function __construct($renderer)
    {
        $this->renderer = $renderer;
    }

    /**
     * The number of times a benchmark should be repeated.
     *
     * @param  int  $times
     * @return $this
     */
    public function repeat($times)
    {
        $this->repeat = $times;

        return $this;
    }

    /**
     * Measure the execution time of the given callbacks.
     *
     * @param  iterable<string|int, \Closure(): mixed>|\Closure(): mixed  $callbacks
     * @return never
     */
    public function measure($callbacks)
    {
        $results = $this->getClosures($callbacks)->map(function ($callback, $key) {
            $average = (float) collect(range(1, $this->repeat))->map(function () use ($callback) {
                gc_collect_cycles();

                $start = hrtime(true);

                $callback();

                return (int) (hrtime(true) - $start);
            })->average();

            return new Result($callback, $key, $average);
        })->values();

        $this->renderer->render($results, $this->repeat);
    }

    /**
     * Get a collection of closures from the given callback(s).
     *
     * @param  iterable<string|int, \Closure(): mixed>|\Closure(): mixed  $callbacks
     * @return \Illuminate\Support\Collection<string|int, \Closure(): mixed>
     *
     * @throws InvalidArgumentException
     */
    protected function getClosures($callbacks)
    {
        $callbacks = collect(Arr::wrap($callbacks));

        return $callbacks->each(function ($callback, $index) use ($callbacks) {
            $line = (new ReflectionClosure($callback))->getStartLine();

            $duplicated = $callbacks->firstWhere(
                fn ($subCallback, $subIndex) => $subIndex !== $index && (new ReflectionClosure($subCallback))->getStartLine() === $line,
            );

            if (! is_null($duplicated)) {
                throw new InvalidArgumentException('The given callbacks must be on separate lines.');
            }
        })->whenEmpty(fn () => throw new InvalidArgumentException('You must provide at least one callback.'));
    }
}
