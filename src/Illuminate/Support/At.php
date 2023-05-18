<?php

namespace Illuminate\Support;

use Exception;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Traits\Macroable;

/**
 * @property-read static $next
 * @property-read static $prev
 * @property-read static $previous
 *
 * @property-read static $startOf
 *
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
 *
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
     * @var bool
     */
    protected $start = false;

    /**
     * Create a new At instance.
     *
     * @param  \DateTimeZone|string|null  $tz
     * @return void
     */
    public function __construct($tz = null)
    {
        $this->tz = $tz;
    }

    /**
     * Find the next moment of time.
     *
     * @param  int  $amount
     * @return $this
     */
    public function next($amount = 1)
    {
         $this->amount = $amount;

         return $this;
    }

    /**
     * Find the previous moment of time.
     *
     * @param  int  $amount
     * @return $this
     */
    public function prev($amount = 1)
    {
         return $this->next(abs($amount) * -1);
    }

    /**
     * Return the current moment.
     *
     * @return Carbon
     */
    public function now()
    {
        return Date::now($this->tz);
    }

    /**
     * Returns the next or previous day of month.
     *
     * @param  int  $day
     * @return \Illuminate\Support\Carbon|\DateTimeInterface
     */
    public function nthOfMonth($day)
    {
        return $this->now()
            ->startOfMonth()
            ->addUnit('month', $this->amount)
            ->setUnitNoOverflow('day', abs($day), 'month')
            ->setTimeFrom()
            ->when($this->start)->startOfDay();
    }

    /**
     * Modify the day of the week of the moment.
     *
     * @param  int  $dayOfWeek
     * @return Carbon
     */
    protected function modifyDayOfWeek($dayOfWeek)
    {
        $moment = $this->now();

        match ($this->amount <=> 0) {
            -1 => $moment->previous($dayOfWeek),
            1 => $moment->next($dayOfWeek),
        };

        return $moment->setTimeFrom()->when($this->start)->startOfDay();
    }

    /**
     * Dynamically get a moment.
     *
     * @param  string  $name
     * @return  $this
     *
     * @throws \Exception
     */
    public function __get(string $name)
    {
        if ($name === 'startOf') {
            $this->start = true;

            return $this;
        }

        return match ($name) {
            'next' => $this->next(),
            'prev',
            'previous' => $this->prev(),
            'second',
            'seconds' => $this->now()->addUnit('second', $this->amount)->when($this->start)->startOfSecond(),
            'minute',
            'minutes' => $this->now()->addUnit('minute', $this->amount)->when($this->start)->startOfMinute(),
            'hour',
            'hours' => $this->now()->addUnit('hour', $this->amount)->when($this->start)->startOfHour(),
            'day',
            'days' => $this->now()->addUnit('day', $this->amount)->when($this->start)->startOfDay(),
            'week',
            'weeks' => $this->now()->addUnit('week', $this->amount)->when($this->start)->startOfWeek(),
            'month',
            'months' => $this->now()->addUnit('month', $this->amount)->when($this->start)->startOfMonth(),
            'year',
            'years' => $this->now()->addUnit('year', $this->amount)->when($this->start)->startOfYear(),
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
