<?php

namespace Illuminate\Queue\Events;

class JobProcessed
{
    /**
     * Create a new event instance.
     *
     * @param  string  $connectionName  The connection name.
     * @param  \Illuminate\Contracts\Queue\Job  $job  The job instance.
     * @param  float|null  $duration  The number of milliseconds the job took to process.
     */
    public function __construct(
        public $connectionName,
        public $job,
        public $duration = null,
    ) {
    }
}
