<?php

namespace Illuminate\Foundation\Testing;

use Illuminate\Support\Facades\Date;

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
        Date::setTestNow(Date::now()->addMilliseconds($this->value));

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
        Date::setTestNow(Date::now()->addSeconds($this->value));

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
        Date::setTestNow(Date::now()->addMinutes($this->value));

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
        Date::setTestNow(Date::now()->addHours($this->value));

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
        Date::setTestNow(Date::now()->addDays($this->value));

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
        Date::setTestNow(Date::now()->addWeeks($this->value));

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
        Date::setTestNow(Date::now()->addYears($this->value));

        return $this->handleCallback($callback);
    }

    /**
     * Travel back to the current time.
     *
     * @return \DateTimeInterface
     */
    public static function back()
    {
        Date::setTestNow();

        return Date::now();
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
                Date::setTestNow();
            });
        }
    }
}
