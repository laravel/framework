<?php

namespace Illuminate\Foundation\Benchmark\Renderers\Concerns;

trait Terminatable
{
    /**
     * The callback that should be used to terminate the benchmark.
     *
     * @var (callable(): never)|null
     */
    protected static $terminateUsing;

    /**
     * Set the callback that should be used to terminate the benchmark.
     *
     * @param  (callable(): never)|null  $callback
     * @return void
     */
    public static function terminateUsing($callback)
    {
        static::$terminateUsing = $callback;
    }

    /**
     * Terminate the benchmark.
     *
     * @return never
     */
    protected function terminate()
    {
        if (static::$terminateUsing) {
            return (static::$terminateUsing)();
        }

        exit(1);
    }
}
