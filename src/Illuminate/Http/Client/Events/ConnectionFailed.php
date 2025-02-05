<?php

namespace Illuminate\Http\Client\Events;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;

class ConnectionFailed
{
    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Http\Client\Request  $request  The request instance.
     * @param  \Illuminate\Http\Client\ConnectionException  $exception  The exception instance.
     * @return void
     */
    public function __construct(
        public Request $request,
        public ConnectionException $exception,
    ) {
    }
}
