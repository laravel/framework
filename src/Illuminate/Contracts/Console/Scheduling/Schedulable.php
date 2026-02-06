<?php

namespace Illuminate\Contracts\Console\Scheduling;

use Illuminate\Console\Scheduling\Event;

interface Schedulable
{
    /**
     * Configure the scheduling event for this job or command.
     *
     * @param  \Illuminate\Console\Scheduling\Event  $event
     * @return void
     */
    public function schedule(Event $event): void;
}
