<?php

namespace Illuminate\Queue\Events;

use RuntimeException;

class JobQueueing
{
    /**
     * The connection name.
     *
     * @var string
     */
    public $connectionName;

    /**
     * The job instance.
     *
     * @var \Closure|string|object
     */
    public $job;

    /**
     * The job payload.
     *
     * @var string|null
     */
    public $payload;

    /**
     * Create a new event instance.
     *
     * @param  string  $connectionName
     * @param  \Closure|string|object  $job
     * @param  string|null  $payload
     * @return void
     */
    public function __construct($connectionName, $job, $payload = null)
    {
        $this->connectionName = $connectionName;
        $this->job = $job;
        $this->payload = $payload;
    }

    /**
     * Get the decoded job payload.
     *
     * @return array
     */
    public function payload()
    {
        if ($this->payload === null) {
            throw new RuntimeException('The job payload was not provided when the event was dispatched.');
        }

        return json_decode($this->payload, true, flags: JSON_THROW_ON_ERROR);
    }
}
