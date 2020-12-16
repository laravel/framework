<?php

namespace Illuminate\Foundation\Testing;

use Illuminate\Support\Carbon;
use Illuminate\Support\CarbonImmutable;

class Wormhole
{
    /**
     * The amount of time to travel.
     *
     * @var int
     */
    public $value;

    /**
     * Create a new wormhole instance.
     *
     * @param  int  $value
     * @return void
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * Travel forward the given number of milliseconds.
     *
     * @param  callable|null  $callback
     * @return mixed
     */
    public function milliseconds($callback = null)
    {
        $this->travelTo(Carbon::now()->addMilliseconds($this->value));

        return $this->handleCallback($callback);
    }

    /**
     * Travel forward the given number of seconds.
     *
     * @param  callable|null  $callback
     * @return mixed
     */
    public function seconds($callback = null)
    {
        $this->travelTo(Carbon::now()->addSeconds($this->value));

        return $this->handleCallback($callback);
    }

    /**
     * Travel forward the given number of minutes.
     *
     * @param  callable|null  $callback
     * @return mixed
     */
    public function minutes($callback = null)
    {
        $this->travelTo(Carbon::now()->addMinutes($this->value));

        return $this->handleCallback($callback);
    }

    /**
     * Travel forward the given number of hours.
     *
     * @param  callable|null  $callback
     * @return mixed
     */
    public function hours($callback = null)
    {
        $this->travelTo(Carbon::now()->addHours($this->value));

        return $this->handleCallback($callback);
    }

    /**
     * Travel forward the given number of days.
     *
     * @param  callable|null  $callback
     * @return mixed
     */
    public function days($callback = null)
    {
        $this->travelTo(Carbon::now()->addDays($this->value));

        return $this->handleCallback($callback);
    }

    /**
     * Travel forward the given number of weeks.
     *
     * @param  callable|null  $callback
     * @return mixed
     */
    public function weeks($callback = null)
    {
        $this->travelTo(Carbon::now()->addWeeks($this->value));

        return $this->handleCallback($callback);
    }

    /**
     * Travel forward the given number of years.
     *
     * @param  callable|null  $callback
     * @return mixed
     */
    public function years($callback = null)
    {
        $this->travelTo(Carbon::now()->addYears($this->value));

        return $this->handleCallback($callback);
    }

    /**
     * Handle the given optional execution callback.
     *
     * @param  callable|null  $callback
     * @return mixed
     */
    protected function handleCallback($callback)
    {
        if ($callback) {
            return tap($callback(), function () {
                Carbon::setTestNow();
                CarbonImmutable::setTestNow();
            });
        }
    }

    /**
     * Travel to another time.
     *
     * @param  \DateTimeInterface  $date
     * @return void
     */
    protected function travelTo(DateTimeInterface $date)
    {
        Carbon::setTestNow($date);
        CarbonImmutable::setTestNow($date);
    }
}
