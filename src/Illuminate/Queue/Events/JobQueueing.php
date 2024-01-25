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
     * The queue name the job is queued on.
     *
     * @var string
     */
    public $queue;

    /**
     * The delay used to queue the job.
     *
     * @var \DateTimeInterface|\DateInterval|int|null
     */
    public $delay;

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
     * Create a new event instance.
     *
     * @param  string  $connectionName
     * @param  string  $queue
     * @param  \DateTimeInterface|\DateInterval|int|null  $delay
     * @param  \Closure|string|object  $job
     * @param  string  $payload
     * @return void
     */
    public function __construct($connectionName, $queue, $delay, $job, $payload)
    {
        $this->connectionName = $connectionName;
        $this->queue = $queue;
        $this->delay = $delay;
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
        return json_decode($this->payload, true, flags: JSON_THROW_ON_ERROR);
    }
}
