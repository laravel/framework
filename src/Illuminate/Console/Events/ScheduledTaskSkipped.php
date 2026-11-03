<?php

namespace Illuminate\Console\Events;

use Illuminate\Console\Scheduling\Event;

class ScheduledTaskSkipped
{
    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Console\Scheduling\Event  $task  The scheduled event being run.
     * @param  \Illuminate\Console\Scheduling\ScheduledTaskSkipReason|null  $reason  The reason why the task was skipped.
     */
    public function __construct(
        public Event $task,
        public $reason = null,
    ) {
    }
}
