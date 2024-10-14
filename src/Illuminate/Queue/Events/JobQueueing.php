<?php

namespace Illuminate\Queue\Events;

class JobQueueing
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
     * @var string
     */
    public $queue;

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
     * The number of seconds the job was delayed.
     *
     * @var int|null
     */
    public $delay;

    /**
     * Create a new event instance.
     *
     * @param  string  $connectionName
     * @param  string  $queue
     * @param  \Closure|string|object  $job
     * @param  string  $payload
     * @param  int|null  $delay
     * @return void
     */
    public function __construct($connectionName, $queue, $job, $payload, $delay)
    {
        $this->connectionName = $connectionName;
        $this->queue = $queue;
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
