<?php

namespace Illuminate\Foundation\Benchmark;

/**
 * @method \Illuminate\Support\Benchmark repeat(int $times)
 * @method \Illuminate\Support\Benchmark measure(iterable|callable $callables)
 *
 * @see \Illuminate\Foundation\Benchmark\Benchmark
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
     * Execute a method against a new pending benchmark instance.
     *
     * @param  string  $method
     * @param  array<int, mixed>  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        $benchmark = new Benchmark($this->renderer);

        return $benchmark->{$method}(...$parameters);
    }
}
