<?php

namespace Illuminate\Http\Client\Events;

use Illuminate\Http\Client\Response;

class ResponseReceived
{
    /**
     * The response returned from an HTTP request.
     *
     * @var \Illuminate\Http\Client\Response
     */
    public $response;

    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Http\Client\Response  $response
     * @return void
     */
    public function __construct(Response $response)
    {
        $this->response = $response;
    }
}
