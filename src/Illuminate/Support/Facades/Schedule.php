<?php

namespace Illuminate\Support\Facades;

use Illuminate\Console\Scheduling\Schedule as ConsoleSchedule;

/**
 * @see \Illuminate\Console\Scheduling\Schedule
 */
class Schedule extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return ConsoleSchedule::class;
    }
}
