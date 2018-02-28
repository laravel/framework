<?php

namespace Illuminate\Console\Scheduling;

use Cron\CronExpression;
use Illuminate\Support\Carbon;

trait Schedulable
{
    use ManagesFrequencies;

    /**
     * The cron expression representing the event's frequency.
     *
     * @var string
     */
    public $expression = '* * * * *';

    /**
     * The timezone the date should be evaluated on.
     *
     * @var \DateTimeZone|string
     */
    public $timezone;

    /**
     * Return a list of due items as a collection.
     * This is meant to be overidden.
     *
     * @return Illuminate\Support\Collection
     */
    public static function areDue()
    {
        return collect([])->filter->isDue();
    }

    /**
     * Determine if the schedule is due to run.
     *
     * @return bool
     */
    public function isDue()
    {
        $date = Carbon::now();

        if ($this->timezone) {
            $date->setTimezone($this->timezone);
        }

        return CronExpression::factory($this->expression)->isDue($date->toDateTimeString());
    }

    /**
     * When the schedule is next due to run.
     *
     * @return Illuminate\Support\Carbon
     */
    public function nextDue()
    {
        return Carbon::instance(CronExpression::factory($this->expression)->getNextRunDate());
    }

    /**
     * When the schedule was last due to run.
     *
     * @return Illuminate\Support\Carbon
     */
    public function lastDue()
    {
        return Carbon::instance(CronExpression::factory($this->expression)->getPreviousRunDate());
    }

    /**
     * Code to be run when the schedule is due.
     * This is meant to be overidden.
     */
    public function runSchedule()
    {
        // return true;
    }
}
