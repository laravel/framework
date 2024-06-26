<?php

namespace Illuminate\Queue\Events;

class JobQueued
{
    /**
     * The connection name.
     *
     * @var string
     */
    public $connectionName;

    /**
     * The queue name.
     *
     * @var string|null
     */
    public $queue;

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
     * @var string
     */
    public $payload;

    /**
     * The amount of time the job was delayed.
     *
     * @var int|null
     */
    public $delay;

    /**
     * Create a new event instance.
     *
     * @param  string  $connectionName
     * @param  string  $queue
     * @param  string|int|null  $id
     * @param  \Closure|string|object  $job
     * @param  string  $payload
     * @param  int|null  $delay
     * @return void
     */
    public function __construct($connectionName, $queue, $id, $job, $payload, $delay)
    {
        $this->connectionName = $connectionName;
        $this->queue = $queue;
        $this->id = $id;
        $this->job = $job;
        $this->payload = $payload;
        $this->delay = $delay;
    }

    /**
     * Get the decoded job payload.
     *
     * @return array
     */
    public function payload()
    {
        return json_decode($this->payload, true, flags: JSON_THROW_ON_ERROR);
    }
}
