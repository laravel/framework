<?php

namespace Illuminate\Foundation\Testing;

use Illuminate\Support\Carbon;

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
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * Travel forward the given number of microseconds.
     *
     * @param  callable|null  $callback
     */
    public function microsecond($callback = null)
    {
        return $this->microseconds($callback);
    }

    /**
     * Travel forward the given number of microseconds.
     *
     * @param  callable|null  $callback
     */
    public function microseconds($callback = null)
    {
        Carbon::setTestNow(Carbon::now()->addMicroseconds($this->value));

        return $this->handleCallback($callback);
    }

    /**
     * Travel forward the given number of milliseconds.
     *
     * @param  callable|null  $callback
     */
    public function millisecond($callback = null)
    {
        return $this->milliseconds($callback);
    }

    /**
     * Travel forward the given number of milliseconds.
     *
     * @param  callable|null  $callback
     */
    public function milliseconds($callback = null)
    {
        Carbon::setTestNow(Carbon::now()->addMilliseconds($this->value));

        return $this->handleCallback($callback);
    }

    /**
     * Travel forward the given number of seconds.
     *
     * @param  callable|null  $callback
     */
    public function second($callback = null)
    {
        return $this->seconds($callback);
    }

    /**
     * Travel forward the given number of seconds.
     *
     * @param  callable|null  $callback
     */
    public function seconds($callback = null)
    {
        Carbon::setTestNow(Carbon::now()->addSeconds($this->value));

        return $this->handleCallback($callback);
    }

    /**
     * Travel forward the given number of minutes.
     *
     * @param  callable|null  $callback
     */
    public function minute($callback = null)
    {
        return $this->minutes($callback);
    }

    /**
     * Travel forward the given number of minutes.
     *
     * @param  callable|null  $callback
     */
    public function minutes($callback = null)
    {
        Carbon::setTestNow(Carbon::now()->addMinutes($this->value));

        return $this->handleCallback($callback);
    }

    /**
     * Travel forward the given number of hours.
     *
     * @param  callable|null  $callback
     */
    public function hour($callback = null)
    {
        return $this->hours($callback);
    }

    /**
     * Travel forward the given number of hours.
     *
     * @param  callable|null  $callback
     */
    public function hours($callback = null)
    {
        Carbon::setTestNow(Carbon::now()->addHours($this->value));

        return $this->handleCallback($callback);
    }

    /**
     * Travel forward the given number of days.
     *
     * @param  callable|null  $callback
     */
    public function day($callback = null)
    {
        return $this->days($callback);
    }

    /**
     * Travel forward the given number of days.
     *
     * @param  callable|null  $callback
     */
    public function days($callback = null)
    {
        Carbon::setTestNow(Carbon::now()->addDays($this->value));

        return $this->handleCallback($callback);
    }

    /**
     * Travel forward the given number of weeks.
     *
     * @param  callable|null  $callback
     */
    public function week($callback = null)
    {
        return $this->weeks($callback);
    }

    /**
     * Travel forward the given number of weeks.
     *
     * @param  callable|null  $callback
     */
    public function weeks($callback = null)
    {
        Carbon::setTestNow(Carbon::now()->addWeeks($this->value));

        return $this->handleCallback($callback);
    }

    /**
     * Travel forward the given number of months.
     *
     * @param  callable|null  $callback
     */
    public function month($callback = null)
    {
        return $this->months($callback);
    }

    /**
     * Travel forward the given number of months.
     *
     * @param  callable|null  $callback
     */
    public function months($callback = null)
    {
        Carbon::setTestNow(Carbon::now()->addMonths($this->value));

        return $this->handleCallback($callback);
    }

    /**
     * Travel forward the given number of years.
     *
     * @param  callable|null  $callback
     */
    public function year($callback = null)
    {
        return $this->years($callback);
    }

    /**
     * Travel forward the given number of years.
     *
     * @param  callable|null  $callback
     */
    public function years($callback = null)
    {
        Carbon::setTestNow(Carbon::now()->addYears($this->value));

        return $this->handleCallback($callback);
    }

    /**
     * Travel back to the current time.
     *
     * @return \DateTimeInterface
     */
    public static function back()
    {
        Carbon::setTestNow();

        return Carbon::now();
    }

    /**
     * Handle the given optional execution callback.
     *
     * @param  callable|null  $callback
     */
    protected function handleCallback($callback)
    {
        if ($callback) {
            return tap($callback(), function () {
                Carbon::setTestNow();
            });
        }
    }
}
