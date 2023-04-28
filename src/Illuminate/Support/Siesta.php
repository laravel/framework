<?php

namespace Illuminate\Support;

use Illuminate\Support\Carbon;
use Carbon\CarbonInterval;
use DateInterval;
use DateTimeInterface;
use PHPUnit\Framework\Assert as PHPUnit;
use RuntimeException;

class Siesta
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
     * Indicates that all sleeping should be faked.
     *
     * @var bool
     */
    protected static $fake = false;

    /**
     * The sequence of sleep durations encountered while faking.
     *
     * @var array
     */
    protected static $pauseSequence = [];

    /**
     * The instance should be "captured" when faking.
     *
     * @var bool
     */
    protected $capture = true;

    /**
     * Create a new Pause instance.
     *
     * @param  int|float|\DateInterval  $duration
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

    /**
     * Sleep until the given timestamp.
     *
     * @param  int|\DateTimeInterface  $timestamp
     * @return void
     */
    public static function until($timestamp)
    {
        if (is_int($timestamp)) {
            $timestamp = Carbon::createFromTimestamp($timestamp);
        }

        static::for(Carbon::now()->diff($timestamp));
    }

    /**
     * Pause execution for the given duration in microseconds.
     *
     * @param  int  $duration
     * @return $this
     */
    public static function usleep($duration)
    {
        return static::for($duration)->microseconds();
    }

    /**
     * Pause execution for the given duration in seconds.
     *
     * @param  int|float  $duration
     * @return $this
     */
    public static function sleep($duration)
    {
        return static::for($duration)->seconds();
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
        if ($this->pending !== 0) {
            throw new RuntimeException('Unknown pause time unit.');
        }

        if ($this->duration->totalMicroseconds <= 0) {
            $this->duration = CarbonInterval::seconds(0);
        }

        if (static::$fake) {
            if ($this->capture) {
                static::$pauseSequence[] = $this;
            }

            return;
        }

        $remaining = $this->duration->copy();

        if ((int) $remaining->totalSeconds > 0) {
            sleep((int) $remaining->totalSeconds);

            $remaining = $remaining->subSeconds((int) $remaining->totalSeconds);
        }


        if ($remaining->totalMicroseconds > 0) {
            usleep($remaining->totalMicroseconds);
        }
    }

    /**
     * Capture all pauses for testing.
     *
     * @param  bool  $value
     * @return void
     */
    public static function fake($value = true)
    {
        static::$fake = $value;

        static::$pauseSequence = [];
    }

    /**
     * Assert the given pause sequence was encountered.
     *
     * @param  array  $sequence
     * @return void
     */
    public static function assertSequence($sequence)
    {
        PHPUnit::assertTrue(($expectedCount = count($sequence)) <= ($actualCount = count(static::$pauseSequence)),
            "Expected [{$expectedCount}] pauses but only found [{$actualCount}]."
        );

        collect($sequence)
            ->zip(static::$pauseSequence)
            ->eachSpread(function (?Siesta $expected, Siesta $actual) {
                if ($expected === null) {
                    return;
                }

                $expected->capture = false;

                PHPUnit::assertTrue(
                    $expected->duration->equalTo($actual->duration),
                    "Expected pause of [{$expected->duration->forHumans(['options' => 0])}] but instead found pause of [{$actual->forHumans(['options' => 0])}]."
                );
            });
    }
}
