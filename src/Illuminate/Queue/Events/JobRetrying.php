<?php

namespace Illuminate\Queue\Events;

class JobRetrying
{
    /**
     * The job retrying object
     *
     * @var \stdClass
     */
    public $job;

    /**
     * The job payload
     *
     * @var array|null
     */
    protected $payload = null;

    /**
     * Create a new event instance.
     *
     * @param  \stdClass   $job
     * @return void
     */
    public function __construct($job)
    {
        $this->job = $job;
    }

    /**
     * The job payload
     *
     * @return array
     */
    public function payload(): array
    {
        if (is_null($this->payload)) {
            $this->payload = json_decode($this->job->payload, true);
        }

        return $this->payload;
    }
}
