<?php

namespace Illuminate\Queue;

class WorkerOptions
{
    /**
     * The number of seconds before a released job will be available.
     *
     * @var int
     */
    public $delay;

    /**
     * The number of seconds to wait in between polling the queue.
     *
     * @var int
     */
    public $sleep;

    /**
     * The maximum amount of times a job may be attempted.
     *
     * @var int
     */
    public $maxTries;

    /**
     * Create a new worker options instance.
     *
     * @param  int  $delay
     * @param  int  $sleep
     * @param  int  $maxTries
     */
    public function __construct($delay = 0, $sleep = 3, $maxTries = 0)
    {
        $this->delay = $delay;
        $this->sleep = $sleep;
        $this->maxTries = $maxTries;
    }
}
