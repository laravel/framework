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
     * Create a new event instance.
     *
     * @param  string  $connectionName
     * @param  string|int|null  $id
     * @param  \Closure|string|object  $job
     * @return void
     */
    public function __construct($connectionName, $id, $job)
    {
        $this->connectionName = $connectionName;
        $this->id = $id;
        $this->job = $job;
    }
}
