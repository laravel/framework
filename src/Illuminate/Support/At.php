<?php

namespace Illuminate\Support;

use Exception;
use Illuminate\Support\Traits\Macroable;

/**
 * @property-read static $next
 * @property-read static $prev
 * @property-read static $previous
 * @property-read static $startOf
 * @property-read static $endOf
 * @property-read \Illuminate\Support\Carbon|\DateTimeInterface $second
 * @property-read \Illuminate\Support\Carbon|\DateTimeInterface $seconds
 * @property-read \Illuminate\Support\Carbon|\DateTimeInterface $minute
 * @property-read \Illuminate\Support\Carbon|\DateTimeInterface $minutes
 * @property-read \Illuminate\Support\Carbon|\DateTimeInterface $hour
 * @property-read \Illuminate\Support\Carbon|\DateTimeInterface $hours
 * @property-read \Illuminate\Support\Carbon|\DateTimeInterface $day
 * @property-read \Illuminate\Support\Carbon|\DateTimeInterface $days
 * @property-read \Illuminate\Support\Carbon|\DateTimeInterface $week
 * @property-read \Illuminate\Support\Carbon|\DateTimeInterface $weeks
 * @property-read \Illuminate\Support\Carbon|\DateTimeInterface $month
 * @property-read \Illuminate\Support\Carbon|\DateTimeInterface $months
 * @property-read \Illuminate\Support\Carbon|\DateTimeInterface $year
 * @property-read \Illuminate\Support\Carbon|\DateTimeInterface $years
 * @property-read \Illuminate\Support\Carbon|\DateTimeInterface $monday
 * @property-read \Illuminate\Support\Carbon|\DateTimeInterface $tuesday
 * @property-read \Illuminate\Support\Carbon|\DateTimeInterface $wednesday
 * @property-read \Illuminate\Support\Carbon|\DateTimeInterface $thursday
 * @property-read \Illuminate\Support\Carbon|\DateTimeInterface $friday
 * @property-read \Illuminate\Support\Carbon|\DateTimeInterface $saturday
 * @property-read \Illuminate\Support\Carbon|\DateTimeInterface $sunday
 */
class At
{
    use Macroable;

    /**
     * The default timezone to use.
     *
     * @var \DateTimeZone|string|null
     */
    protected $tz = null;

    /**
     * How much to forward or rewind the moment.
     *
     * @var int
     */
    protected $amount = 1;

    /**
     * If it should rewind to the start of the given unit.
     *
     * @var int
     */
    protected $start = 0;

    /**
     * Create a new At instance.
     *
     * @param  int  $amount
     * @param  \DateTimeZone|string|null  $tz
     * @return void
     */
    public function __construct($amount, $tz = null)
    {
        $this->amount = $amount;
        $this->tz = $tz;
    }

    /**
     * Find the next moment of time.
     *
     * @param  int  $amount
     * @return $this
     */
    public static function next($amount = 1, $tz = null)
    {
        return new static($amount, $tz);
    }

    /**
     * Find the previous moment of time.
     *
     * @param  int  $amount
     * @return $this
     */
    public static function prev($amount = 1, $tz = null)
    {
        return static::next(abs($amount) * -1, $tz);
    }

    /**
     * Return the current moment.
     *
     * @return \Illuminate\Support\Carbon|\DateTimeInterface
     */
    protected function now()
    {
        return new Carbon(null, $this->tz);
    }

    /**
     * Set the start of the next time unit.
     *
     * @return $this
     */
    public function startOf()
    {
        $this->start = 1;

        return $this;
    }

    /**
     * Set the end of the next time unit.
     *
     * @return $this
     */
    public function endOf()
    {
        $this->start = -1;

        return $this;
    }

    /**
     * Modifies the time to the start of end of a given unit.
     *
     * @param  \Illuminate\Support\Carbon  $time
     * @param  string  $unit
     * @return \Illuminate\Support\Carbon
     */
    protected function edgeTime($time, $unit)
    {
        return match($this->start) {
            1 => $time->startOf($unit),
            -1 => $time->endOf($unit),
            default => $time
        };
    }

    /**
     * Returns the next or previous day of month.
     *
     * @param  int  $day
     * @return \Illuminate\Support\Carbon|\DateTimeInterface
     */
    public function nthOfMonth($day)
    {
        $now = $this->now();

        return $now
            ->clone()
            ->startOfMonth()
            ->addUnit('month', $this->amount)
            ->setUnitNoOverflow('day', abs($day), 'month')
            ->setTimeFrom($now)
            ->when($this->start !== 0, fn($time) => $this->edgeTime($time, 'day'));
    }

    /**
     * Adds a month without overflowing.
     *
     * @return \Illuminate\Support\Carbon|\DateTimeInterface
     */
    protected function addMonth()
    {
        $now = $this->now();

        return $now
            ->clone()
            ->setUnit('day', 1)
            ->addUnit('month', $this->amount)
            ->setUnitNoOverflow('day', $now->day, 'month')
            ->setTimeFrom($now)
            ->when($this->start !== 0, fn($time) => $this->edgeTime($time, 'month'));
    }

    /**
     * Modify the day of the week of the moment.
     *
     * @param  int  $dayOfWeek
     * @return Carbon
     */
    protected function modifyDayOfWeek($dayOfWeek)
    {
        $now = $this->now();

        match ($this->amount <=> 0) {
            -1 => $now->previous($dayOfWeek),
            1 => $now->next($dayOfWeek),
        };

        return $now->setTimeFrom()->when($this->start !== 0, fn($time) => $this->edgeTime($time, 'day'));
    }

    /**
     * Dynamically get a moment.
     *
     * @param  string  $name
     * @return $this
     *
     * @throws \Exception
     */
    public function __get(string $name)
    {
        return match ($name) {
            'startOf' => $this->startOf(),
            'endOf' => $this->endOf(),
            'next' => $this->next(),
            'prev',
            'previous' => $this->prev(),
            'second',
            'seconds' => $this->now()->addUnit('second', $this->amount)->when($this->start !== 0, fn($time) => $this->edgeTime($time, 'second')),
            'minute',
            'minutes' => $this->now()->addUnit('minute', $this->amount)->when($this->start !== 0, fn($time) => $this->edgeTime($time, 'minute')),
            'hour',
            'hours' => $this->now()->addUnit('hour', $this->amount)->when($this->start !== 0, fn($time) => $this->edgeTime($time, 'hour')),
            'day',
            'days' => $this->now()->addUnit('day', $this->amount)->when($this->start !== 0, fn($time) => $this->edgeTime($time, 'day')),
            'week',
            'weeks' => $this->now()->addUnit('week', $this->amount)->when($this->start !== 0, fn($time) => $this->edgeTime($time, 'week')),
            'month',
            'months' => $this->addMonth(),
            'year',
            'years' => $this->now()->addUnit('year', $this->amount)->when($this->start !== 0, fn($time) => $this->edgeTime($time, 'year')),
            'monday' => $this->modifyDayOfWeek(Carbon::MONDAY),
            'tuesday' => $this->modifyDayOfWeek(Carbon::TUESDAY),
            'wednesday' => $this->modifyDayOfWeek(Carbon::WEDNESDAY),
            'thursday' => $this->modifyDayOfWeek(Carbon::THURSDAY),
            'friday' => $this->modifyDayOfWeek(Carbon::FRIDAY),
            'saturday' => $this->modifyDayOfWeek(Carbon::SATURDAY),
            'sunday' => $this->modifyDayOfWeek(Carbon::SUNDAY),
            default => throw new Exception('Unable to access undefined property on '.__CLASS__.': '.$name)
        };
    }
}
