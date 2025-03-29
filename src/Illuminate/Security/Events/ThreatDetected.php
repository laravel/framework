<?php

namespace Illuminate\Security\Events;

use Illuminate\Http\Request;

class ThreatDetected
{
    /**
     * The detected threat information.
     *
     * @var array
     */
    public $threat;

    /**
     * The HTTP request.
     *
     * @var \Illuminate\Http\Request
     */
    public $request;

    /**
     * Create a new event instance.
     *
     * @param  array  $threat
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function __construct(array $threat, Request $request)
    {
        $this->threat = $threat;
        $this->request = $request;
    }
} 