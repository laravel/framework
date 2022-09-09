<?php

namespace Illuminate\Support;

class Timebox
{
    /**
     * Is the timebox allowed to do an early return
     *
     * @var bool
     */
    public $earlyReturn = false;

    /**
     * @param  callable  $callback
     * @param  int  $microseconds
     * @return mixed
     */
    public function make(callable $callback, int $microseconds)
    {
        $start = microtime(true);

        $result = $callback($this);

        $remainder = $microseconds - ((microtime(true) - $start) * 1000000);

        if (! $this->earlyReturn && $remainder > 0) {
            $this->usleep($remainder);
        }

        return $result;
    }

    public function returnEarly(): self
    {
        $this->earlyReturn = true;

        return $this;
    }

    public function dontReturnEarly(): self
    {
        $this->earlyReturn = false;

        return $this;
    }

    /**
     * @param  $microseconds
     * @return void
     */
    protected function usleep($microseconds)
    {
        usleep($microseconds);
    }
}
