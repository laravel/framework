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
     * @var \Illuminate\Support\Carbon|null
     */
    public $startedAt;

    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Support\Carbon|null  $startedAt
     */
    public function __construct($request, $startedAt)
    {
        $this->request = $request;
        $this->startedAt = $startedAt;
    }
}
