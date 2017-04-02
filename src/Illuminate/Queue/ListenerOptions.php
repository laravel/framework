<?php

namespace Illuminate\Queue;

class ListenerOptions extends WorkerOptions
{
    /**
     * Any extra parameters to pass through to the worker command.
     *
     * @var string
     */
    public $parameters;

    /**
     * Create a new listener options instance.
     *
     * @param  int  $delay
     * @param  int  $memory
     * @param  int  $timeout
     * @param  int  $sleep
     * @param  int  $maxTries
     * @param  bool  $force
     * @param  string  $parameters
     */
    public function __construct($delay = 0, $memory = 128, $timeout = 60, $sleep = 3, $maxTries = 0, $force = false, $parameters = '')
    {
        $this->parameters = $parameters;

        parent::__construct($delay, $memory, $timeout, $sleep, $maxTries, $force);
    }
}
