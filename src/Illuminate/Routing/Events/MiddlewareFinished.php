<?php

namespace Illuminate\Routing\Events;

class MiddlewareFinished
{
    /**
     * The middleware that has finished running.
     *
     * @var mixed
     */
    public $middleware;

    /**
     * Create a new event instance.
     *
     * @param $middleware
     */
    public function __construct($middleware)
    {
        $this->middleware = $middleware;
    }
}
