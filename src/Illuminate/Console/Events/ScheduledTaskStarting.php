<?php

namespace Illuminate\Console\Events;

use Illuminate\Console\Scheduling\Event;

class ScheduledTaskStarting
{
    /**
     * The scheduled event being run.
     *
     * @var \Illuminate\Console\Scheduling\Event
     */
    public $event;

    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Console\Scheduling\Event  $event
     * @return void
     */
    public function __construct(Event $event)
    {
        $this->event = $event;
    }
}
