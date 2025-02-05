<?php

namespace Illuminate\Http\Client\Events;

use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\Response;

class ResponseReceived
{
    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Http\Client\Request  $request  The request instance.
     * @param  \Illuminate\Http\Client\Response  $response  The response instance.
     * @return void
     */
    public function __construct(
        public Request $request,
        public Response $response,
    ) {
    }
}
