<?php

namespace Illuminate\Support;

use Carbon\CarbonInterval;
use DateInterval;

class Pause
{
    /**
     * The total duration to pause execution.
     *
     * @var \Carbon\CarbonInterval
     */
    public $duration;

    /**
     * The pending duration to pause execution.
     *
     * @var int|float
     */
    protected $pending;

    /**
     * Create a new Pause instance.
     *
     * @param  int|float  $duration
     * @return void
     */
    public function __construct($duration)
    {
        if ($duration instanceof DateInterval) {
            $this->duration = CarbonInterval::instance($duration);

            $this->pending = 0;
        } else {
            $this->duration = CarbonInterval::seconds(0);

            $this->pending = $duration;
        }
    }

    /**
     * Pause for the given duration.
     *
     * @param  int|float|DateInterval  $duration
     * @return static
     */
    public static function for($duration)
    {
        return new static($duration);
    }

    public static function until($timestamp)
    {
        //
    }

    /**
     * Pause execution for the given number of minutes.
     *
     * @return $this
     */
    public function minutes()
    {
        $this->duration->addMinutes($this->pending);

        $this->pending = 0;

        return $this;
    }

    /**
     * Pause execution for the given number of minutes.
     *
     * @return $this
     */
    public function minute()
    {
        return $this->minutes();
    }

    /**
     * Pause execution for the given number of seconds.
     */
    public function seconds()
    {
        $this->duration->addSeconds($this->pending);

        $this->pending = 0;

        return $this;
    }

    /**
     * Pause execution for the given number of seconds.
     *
     * @return $this
     */
    public function second()
    {
        return $this->seconds();
    }

    /**
     * Pause execution for the given number of milliseconds.
     *
     * @return $this
     */
    public function milliseconds()
    {
        $this->duration->addMilliseconds($this->pending);

        $this->pending = 0;

        return $this;
    }

    /**
     * Pause execution for the given number of milliseconds.
     *
     * @return $this
     */
    public function millisecond()
    {
        return $this->milliseconds();
    }

    /**
     * Pause execution for the given number of microseconds.
     *
     * @return $this
     */
    public function microseconds()
    {
        $this->duration->addMicroseconds($this->pending);

        $this->pending = 0;

        return $this;
    }

    /**
     * Pause execution for the given number of microseconds.
     *
     * @return $this
     */
    public function microsecond()
    {
        return $this->microseconds();
    }

    /**
     * Add additional time to the execution pause.
     *
     * @param  int|float  $duration
     * @return $this
     */
    public function and($duration)
    {
        $this->pending = $duration;

        return $this;
    }

    /**
     * Handle the object's destruction.
     *
     * @return void
     */
    public function __destruct()
    {
        $remaining = $this->duration->copy();

        if ((int) $remaining->totalSeconds > 0) {
            static::sleep((int) $remaining->totalSeconds);

            $remaining = $remaining->subSeconds((int) $remaining->totalSeconds);
        }


        if ($remaining->totalMicroseconds > 0) {
            static::usleep($remaining->totalMicroseconds);
        }
    }

    /**
     * Pause execution for the given duration in microseconds.
     *
     * @param  int  $duration
     * @return void
     */
    public static function usleep($duration)
    {
        usleep($duration);
    }

    /**
     * Pause execution for the given duration in seconds.
     *
     * @param  int  $duration
     * @return void
     */
    public static function sleep($duration)
    {
        sleep($duration);
    }
}
