<?php

namespace Illuminate\Http\Client\Events;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;

class ConnectionFailed
{
    /**
     * Create a new event instance.
     */
    public function __construct(
        public Request $request,
        public ConnectionException $exception,
    ) {
    }
}
