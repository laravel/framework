<?php

namespace Illuminate\Http\Client\Events;

use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\Response;

class ResponseReceived
{
    /**
     * Create a new event instance.
     */
    public function __construct(
        public Request $request,
        public Response $response,
    ) {
    }
}
