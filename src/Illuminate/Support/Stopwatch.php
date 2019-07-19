<?php

namespace Illuminate\Support;

class Stopwatch
{
    /**
     * All of the current timers.
     *
     * @var array
     */
    public $timers = [];

    /**
     * Start a new timer.
     *
     * @param  string  $key
     * @return void
     */
    public function start($key)
    {
        $this->timers[$key] = microtime(true);
    }

    /**
     * Check a given timer and get the elapsed time in milliseconds.
     *
     * @param  string  $key
     * @param  int  $precision
     * @return float|null
     */
    public function check($key, $precision = 2)
    {
        if (isset($this->timers[$key])) {
            return round((microtime(true) - $this->timers[$key]), $precision);
        }
    }
}
