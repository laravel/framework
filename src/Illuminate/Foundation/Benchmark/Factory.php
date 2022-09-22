<?php

namespace Illuminate\Foundation\Benchmark;

/**
 * @method \Illuminate\Support\Benchmark repeat(int $times)
 * @method \Illuminate\Support\Benchmark measure(iterable|callable $callables)
 *
 * @see \Illuminate\Foundation\Benchmark\PendingBenchmark
 */
class Factory
{
    /**
     * The renderer implementation..
     *
     * @var \Illuminate\Contracts\Foundation\BenchmarkRenderer
     */
    protected $renderer;

    /**
     * Create a new benchmark factory instance.
     *
     * @param  \Illuminate\Contracts\Foundation\BenchmarkRenderer  $renderer
     * @return void
     */
    public function __construct($renderer)
    {
        $this->renderer = $renderer;
    }

    /**
     * Creates a new "pending" benchmark instance.
     *
     * @return \Illuminate\Foundation\Benchmark\PendingBenchmark
     */
    public function newPendingBenchmark()
    {
        return new PendingBenchmark($this->renderer);
    }

    /**
     * Execute a method against a new "pending" benchmark instance.
     *
     * @param  string  $method
     * @param  array<int, mixed>  $parameters
     * @return \Illuminate\Foundation\Benchmark\PendingBenchmark|\Illuminate\Foundation\Benchmark\Result
     */
    public function __call($method, $parameters)
    {
        return $this->newPendingBenchmark()->{$method}(...$parameters);
    }
}
