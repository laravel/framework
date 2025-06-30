<?php

namespace Illuminate\Foundation\Http\Events;

class RequestStarted
{
    /**
     * The request instance.
     *
     * @var \Illuminate\Http\Request
     */
    public $request;


    /**
     * When the request started.
     *
     * @var \Carbon\CarbonImmutable
     */
    public $startedAt;

    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Carbon\CarbonImmutable  $startedAt
     */
    public function __construct($request, $startedAt)
    {
        $this->request = $request;
        $this->startedAt = $startedAt;
    }
}
