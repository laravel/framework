<?php

namespace Illuminate\Foundation\Benchmark;

class Result
{
    /**
     * The callback that was measured.
     *
     * @var \Closure(): mixed
     */
    public $callback;

    /**
     * The key of the callback.
     *
     * @var string|int
     */
    public $key;

    /**
     * The average execution time.
     *
     * @var float
     */
    public $average;

    /**
     * Create a new benchmark result instance.
     *
     * @param  \Closure(): mixed  $callback
     * @param  string|int  $key
     * @param  float  $average
     * @return void
     */
    public function __construct($callback, $key, $average)
    {
        $this->callback = $callback;
        $this->key = $key;
        $this->average = $average;
    }
}
