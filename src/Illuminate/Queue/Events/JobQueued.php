<?php

namespace Illuminate\Queue\Events;

use RuntimeException;

class JobQueued
{
    /**
     * The connection name.
     *
     * @var string
     */
    public $connectionName;

    /**
     * The job ID.
     *
     * @var string|int|null
     */
    public $id;

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
     * The queue name the job is queued on.
     *
     * @var string|null
     */
    public $queue;

    /**
     * The delay used to queue the job.
     *
     * @var \DateTimeInterface|\DateInterval|int|null
     */
    public $delay;

    /**
     * Create a new event instance.
     *
     * @param  string  $connectionName
     * @param  string|int|null  $id
     * @param  \Closure|string|object  $job
     * @param  string|null  $payload
     * @param  string|null  $queue
     * @param  \DateTimeInterface|\DateInterval|int|null  $delay
     * @return void
     */
    public function __construct($connectionName, $id, $job, $payload = null, $queue = null, $delay = null)
    {
        $this->connectionName = $connectionName;
        $this->id = $id;
        $this->job = $job;
        $this->payload = $payload;
        $this->queue = $queue;
        $this->delay = $delay;
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
