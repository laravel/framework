<?php

namespace Illuminate\Support;

use Carbon\Carbon;
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
     * @var int|float|null
     */
    protected $pending = null;

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
     * Indicates if the instance should sleep.
     *
     * @var bool
     */
    protected $shouldSleep = true;

    /**
     * Create a new Siesta instance.
     *
     * @param  int|float|\DateInterval  $duration
     * @return void
     */
    public function __construct($duration)
    {
        if (! $duration instanceof DateInterval) {
            $this->duration = CarbonInterval::microsecond(0);

            $this->pending = $duration;
        } else {
            $duration = CarbonInterval::instance($duration);

            if ($duration->totalMicroseconds < 0) {
                $duration = CarbonInterval::seconds(0);
            }

            $this->duration = $duration;
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
     * @return static
     */
    public static function until($timestamp)
    {
        if (is_int($timestamp)) {
            $timestamp = Carbon::createFromTimestamp($timestamp);
        }

        return new static(Carbon::now()->diff($timestamp));
    }

    /**
     * Sleep for the duration in microseconds.
     *
     * @param  int  $duration
     * @return static
     */
    public static function usleep($duration)
    {
        return (new static($duration))->microseconds();
    }

    /**
     * Sleep for the duration in seconds.
     *
     * @param  int|float  $duration
     * @return static
     */
    public static function sleep($duration)
    {
        return (new static($duration))->seconds();
    }

    /**
     * Sleep for the duration in minutes.
     *
     * @return $this
     */
    public function minutes()
    {
        $this->duration->add('minutes', $this->pullPending());

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
        $this->duration->add('seconds', $this->pullPending());

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
        $this->duration->add('milliseconds', $this->pullPending());

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
        $this->duration->add('microseconds', $this->pullPending());

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
        if (! $this->shouldSleep) {
            return;
        }

        if ($this->pending !== null) {
            throw new RuntimeException('Unknown duration unit.');
        }

        if (static::$fake) {
            static::$sequence[] = $this->duration;

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
     * Resolve the pending duration.
     *
     * @return int|float
     */
    protected function pullPending()
    {
        if ($this->pending === null) {
            $this->shouldNotSleep();

            throw new RuntimeException('No duration specified.');
        }

        if ($this->pending < 0) {
            $this->pending = 0;
        }

        return tap($this->pending, function () {
            $this->pending = null;
        });
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
     * Assert the given sleeping occurred the a specific number of times.
     *
     * @param  \Closure|static  $expected
     * @param  int  $times
     * @return void
     */
    public static function assertSlept($expected, $times = 1)
    {
        $callback = $expected instanceof static
            ? fn (Siesta $actual) => $actual->duration->equalTo($expected->shouldNotSleep()->duration)
            : $expected;

        $count = collect(static::$sequence)->filter($expected)->count();

        PHPUnit::assertSame(
            $times,
            $count,
            "The expected siesta was found [{$count}] times instead of [{$times}]."
        );
    }

    /**
     * Assert the given sleep sequence was encountered.
     *
     * @param  array  $sequence
     * @return void
     */
    public static function assertSequence($sequence)
    {
        static::assertSleptTimes(count($sequence));

        collect($sequence)
            ->zip(static::$sequence)
            ->eachSpread(function (?Siesta $expected, CarbonInterval $actual) {
                if ($expected === null) {
                    return;
                }

                PHPUnit::assertTrue(
                    $expected->shouldNotSleep()->duration->equalTo($actual),
                    vsprintf("Expected siesta duration of [%s] but instead found duration of [%s].", [
                        $expected->duration->cascade()->forHumans([
                            'options' => 0,
                            'minimumUnit' => 'microsecond',
                        ]),
                        $actual->cascade()->forHumans([
                            'options' => 0,
                            'minimumUnit' => 'microsecond',
                        ]),
                    ])
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
        foreach (static::$sequence as $duration) {
            PHPUnit::assertSame(0, $duration->totalMicroseconds, vsprintf('Unexpected siesta duration of [%s] found.', [
                $duration->cascade()->forHumans([
                    'options' => 0,
                    'minimumUnit' => 'microsecond',
                ]),
            ]));
        }
    }

    /**
     * Assert sleeping occurred the given times.
     *
     * @param  int  $expected
     * @return void
     */
    public static function assertSleptTimes($expected)
    {
        PHPUnit::assertSame($expected, $count = count(static::$sequence), "Expected [{$expected}] siestas but found [{$count}].");
    }

    /**
     * Indicate that the instance should not sleep.
     *
     * @return $this
     */
    protected function shouldNotSleep()
    {
        $this->shouldSleep = false;

        return $this;
    }
}
