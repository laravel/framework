<?php

namespace Illuminate\Console\Scheduling;

use InvalidArgumentException;

use const Illuminate\Support\Date\{DAYS_PER_WEEK, HOURS_PER_DAY, MONTHS_PER_YEAR};

trait ValidatesFrequencies
{
    /**
     * @param  int  $hours
     */
    protected function validateHour(int $hour)
    {
        if ($hour < 0 || $hour > HOURS_PER_DAY) {
            throw new InvalidArgumentException('Hour cron expression component must be between 0 and '.HOURS_PER_DAY.". [$hour] given");
        }
    }

    /**
     * @param  int  $dayOfWeek
     */
    protected function validateDayOfWeek(int $dayOfWeek)
    {
        if ($dayOfWeek < 0 || $dayOfWeek > DAYS_PER_WEEK) {
            throw new InvalidArgumentException('Day of week cron expression component must be between 0 and '.DAYS_PER_WEEK.". [$dayOfWeek] given");
        }
    }

    /**
     * @param  int  $month
     */
    protected function validateMonth(int $month)
    {
        if ($month < 0 || $month > MONTHS_PER_YEAR) {
            throw new InvalidArgumentException('Month cron expression component must be between 0 and '.MONTHS_PER_YEAR.". [$month] given");
        }
    }

    /**
     * @param  int  $month
     */
    protected function validateDayOfMonth(int $dayOfMonth)
    {
        if ($month < 0 || $month > MONTHS_PER_YEAR) {
            throw new InvalidArgumentException('Month cron expression component must be between 0 and '.MONTHS_PER_YEAR.". [$month] given");
        }
    }
}
