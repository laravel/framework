<?php

namespace Illuminate\Foundation\Testing\Concerns;

use Illuminate\Foundation\Testing\Wormhole;
use Illuminate\Support\Carbon;

trait InteractsWithTime
{
    /**
     * Freeze time.
     *
     * @param  callable|null  $callback
     * @return mixed
     */
    public function freezeTime($callback = null)
    {
        return $this->travelTo(Carbon::now(), $callback);
    }

    /**
     * Freeze time at the beginning of the current second.
     *
     * @param  callable|null  $callback
     * @return mixed
     */
    public function freezeSecond($callback = null)
    {
        return $this->travelTo(Carbon::now()->startOfSecond(), $callback);
    }

    /**
     * Begin travelling to another time.
     *
     * @param  int  $value
     * @return \Illuminate\Foundation\Testing\Wormhole
     */
    public function travel($value)
    {
        return new Wormhole($value);
    }

    /**
     * Begin traveling into the past.
     *
     * @param  int  $value
     * @return \Illuminate\Foundation\Testing\Wormhole
     */
    public function travelIntoThePast($value)
    {
        $this->assertGreaterThanOrEqual(1, $value, 'The given value should be a positive integer');

        return new Wormhole($value * -1);
    }

    /**
     * Travel to another time.
     *
     * @param  \DateTimeInterface|\Closure|\Illuminate\Support\Carbon|string|bool|null  $date
     * @param  callable|null  $callback
     * @return mixed
     */
    public function travelTo($date, $callback = null)
    {
        Carbon::setTestNow($date);

        if ($callback) {
            return tap($callback($date), function () {
                Carbon::setTestNow();
            });
        }
    }

    /**
     * Travel back to the current time.
     *
     * @return \DateTimeInterface
     */
    public function travelBack()
    {
        return Wormhole::back();
    }
}
