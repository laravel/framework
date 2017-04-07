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
     * Verbosity level (v/vv/vvv).
     *
     * @var string
     */
    public $verbosity;

    /**
     * Create a new listener options instance.
     *
     * @param  string  $environment
     * @param  int  $delay
     * @param  int  $memory
     * @param  int  $timeout
     * @param  int  $sleep
     * @param  int  $maxTries
     * @param  bool  $force
     * @param  string  $verbosity
     */
    public function __construct($environment = null, $delay = 0, $memory = 128, $timeout = 60, $sleep = 3, $maxTries = 0, $force = false, $verbosity = null)
    {
        $this->environment = $environment;
        $this->verbosity = $verbosity;

        parent::__construct($delay, $memory, $timeout, $sleep, $maxTries, $force);
    }
}
