<?php

namespace Illuminate\Queue;

use Carbon\Carbon;
use DateTimeInterface;

trait CalculatesDelays
{
    /**
     * Get the number of seconds until the given DateTime.
     *
     * @param  \DateTimeInterface  $delay
     * @return int
     */
    protected function secondsUntil($delay)
    {
        return $delay instanceof DateTimeInterface
                            ? max(0, $delay->getTimestamp() - $this->currentTime())
                            : (int) $delay;
    }

    /**
     * Get the current system time as a UNIX timestamp.
     *
     * @return int
     */
    protected function currentTime()
    {
        return Carbon::now()->getTimestamp();
    }
}
