<?php

namespace Illuminate\Queue\Events;

class JobInterrupted
{
    /**
     * Create a new event instance.
     *
     * @param  string  $connectionName  The connection name.
     * @param  \Illuminate\Contracts\Queue\Job  $job  The job instance.
     * @param  int  $signal  The signal that was sent.
     */
    public function __construct(
        public $connectionName,
        public $job,
        public $signal,
    ) {
    }
}
