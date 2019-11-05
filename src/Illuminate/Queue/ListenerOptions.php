<?php

namespace Illuminate\Queue;

class ListenerOptions extends WorkerOptions
{
    /**
     * The environment the worker should run in.
     *
     * @var string
     */
    public $environment;

    /**
     * Create a new listener options instance.
     *
     * @param  string|null  $environment
     * @param  int  $delay
     * @param  int  $memory
     * @param  int  $timeout
     * @param  int  $sleep
     * @param  int  $maxTries
     * @param  bool  $force
     * @return void
     */
    public function __construct($environment = null, $delay = 0, $memory = 128, $timeout = 60, $sleep = 3, $maxTries = 1, $force = false)
    {
        $this->environment = $environment;

        parent::__construct($delay, $memory, $timeout, $sleep, $maxTries, $force);
    }
}
