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
     * @param  string  $name
     * @param  string|null  $environment
     * @param  int  $backoff
     * @param  int  $memory
     * @param  int  $timeout
     * @param  int  $sleep
     * @param  int  $maxTries
     * @param  bool  $force
     * @return void
     */
    public function __construct($name = 'default', $environment = null, $backoff = 0, $memory = 128, $timeout = 60, $sleep = 3, $maxTries = 1, $force = false)
    {
        $this->environment = $environment;

        parent::__construct($name, $backoff, $memory, $timeout, $sleep, $maxTries, $force);
    }
}
