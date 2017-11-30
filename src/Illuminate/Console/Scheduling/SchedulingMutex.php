<?php

namespace Illuminate\Console\Scheduling;

use Illuminate\Support\Carbon;

interface SchedulingMutex
{
    /**
     * Attempt to obtain a scheduling mutex for the given event.
     *
     * @param  \Illuminate\Console\Scheduling\Event  $event
     * @param  \Illuminate\Support\Carbon  $time
     * @return bool
     */
    public function create(Event $event, Carbon $time);

    /**
     * Determine if a scheduling mutex exists for the given event.
     *
     * @param  \Illuminate\Console\Scheduling\Event  $event
     * @param  \Illuminate\Support\Carbon  $time
     * @return bool
     */
    public function exists(Event $event, Carbon $time);
}
