<?php

namespace Illuminate\Queue\Events;

class JobQueued
{
    /**
     * @var mixed
     */
    public $command;

    /**
     * @var mixed
     */
    public $jobId;

    /**
     * JobQueued constructor.
     *
     * @param mixed $command
     * @param mixed $jobId
     * @return void
     */
    public function __construct($command, $jobId)
    {
        $this->command = $command;
        $this->jobId = $jobId;
    }
}
