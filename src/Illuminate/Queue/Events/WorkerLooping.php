<?php

namespace Illuminate\Queue\Events;

use Illuminate\Queue\WorkerOptions;

class WorkerLooping
{
    /**
     * @var WorkerOptions
     */
    private $workerOptions;

    /**
     * Create a new event instance.
     *
     * @param  WorkerOptions  $workerOptions
     * @return void
     */
    public function __construct(WorkerOptions $workerOptions)
    {
        $this->workerOptions = $workerOptions;
    }
}
