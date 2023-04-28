<?php

namespace Illuminate\Support;

use Carbon\CarbonInterval;
use DateInterval;
use PHPUnit\Framework\Assert as PHPUnit;
use RuntimeException;

class Siesta
{
    /**
     * The total duration to sleep.
     *
     * @var \Carbon\CarbonInterval
     */
    public $duration;

    /**
     * The pending duration to sleep.
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
     * @var array<int, \Carbon\CarbonInterval>
     */
    protected static $sequence = [];

    /**
     * The instance should be "captured" when faking.
     *
     * @var bool
     */
    protected $capture = true;

    /**
     * Create a new Siesta instance.
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
     * Sleep for the given duration.
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
     * Sleep for the duration in microseconds.
     *
     * @param  int  $duration
     * @return $this
     */
    public static function usleep($duration)
    {
        return static::for($duration)->microseconds();
    }

    /**
     * Sleep for the duration in seconds.
     *
     * @param  int|float  $duration
     * @return $this
     */
    public static function sleep($duration)
    {
        return static::for($duration)->seconds();
    }

    /**
     * Sleep for the duration in minutes.
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
     * Sleep for the duration in minutes.
     *
     * @return $this
     */
    public function minute()
    {
        return $this->minutes();
    }

    /**
     * Sleep for the duration in seconds.
     *
     * @return $this
     */
    public function seconds()
    {
        $this->duration->addSeconds($this->pending);

        $this->pending = 0;

        return $this;
    }

    /**
     * Sleep for the duration in seconds.
     *
     * @return $this
     */
    public function second()
    {
        return $this->seconds();
    }

    /**
     * Sleep for the duration in milliseconds.
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
     * Sleep for the duration in milliseconds.
     *
     * @return $this
     */
    public function millisecond()
    {
        return $this->milliseconds();
    }

    /**
     * Sleep for the duration in microseconds.
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
     * Sleep for the duration in microseconds.
     *
     * @return $this
     */
    public function microsecond()
    {
        return $this->microseconds();
    }

    /**
     * Add additional time to sleep for.
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
            throw new RuntimeException('Unknown Siesta duration unit.');
        }

        if ($this->duration->totalMicroseconds <= 0) {
            $this->duration = CarbonInterval::seconds(0);
        }

        if (static::$fake) {
            if ($this->capture) {
                static::$sequence[] = $this->duration;
            }

            return;
        }

        $remaining = $this->duration->copy();

        $seconds = (int) $remaining->totalSeconds;

        if ($seconds > 0) {
            sleep($seconds);

            $remaining = $remaining->subSeconds($seconds);
        }

        $microseconds = (int) $remaining->totalMicroseconds;

        if ($microseconds > 0) {
            usleep($microseconds);
        }
    }

    /**
     * Stay awake and captured any attempts to sleep.
     *
     * @param  bool  $value
     * @return void
     */
    public static function fake($value = true)
    {
        static::$fake = $value;

        static::$sequence = [];
    }

    /**
     * Assert the given sleep sequence was encountered.
     *
     * @param  array  $sequence
     * @return void
     */
    public static function assertSequence($sequence)
    {
        PHPUnit::assertTrue(($expectedCount = count($sequence)) <= ($actualCount = count(static::$sequence)),
            "Expected [{$expectedCount}] pauses but only found [{$actualCount}]."
        );

        collect($sequence)
            ->zip(static::$sequence)
            ->eachSpread(function (?Siesta $expected, CarbonInterval $actual) {
                if ($expected === null) {
                    return;
                }

                $expected->capture = false;

                PHPUnit::assertTrue(
                    $expected->duration->equalTo($actual),
                    "Expected pause of [{$expected->duration->forHumans(['options' => 0])}] but instead found pause of [{$actual->forHumans(['options' => 0])}]."
                );
            });
    }

    /**
     * Assert that no sleeping occurred.
     *
     * @return void
     */
    public static function assertInsomniac()
    {
        PHPUnit::assertSame(0, $count = count(static::$sequence), "Expected [0] pauses but found [{$count}].");
    }

    /**
     * Assert sleeping occurred the given times.
     *
     * @param  int  $expected
     * @return void
     */
    public static function assertSleptTimes($expected)
    {
        PHPUnit::assertSame($expected, $count = count(static::$sequence), "Expected [{$expected}] pauses but found [{$count}].");
    }
}
