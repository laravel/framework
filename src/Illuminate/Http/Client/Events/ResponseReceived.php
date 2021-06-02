<?php

namespace Illuminate\Http\Client\Events;

use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\Response;

class ResponseReceived
{
    /**
     * The request object used by the Http Client.
     *
     * @var \Illuminate\Http\Client\Request
     */
    public $request;

    /**
     * The response returned from an HTTP request.
     *
     * @var \Illuminate\Http\Client\Response
     */
    public $response;

    /**
     * Create a new event instance.
     *
     * @param \Illuminate\Http\Client\Request $request
     * @param  \Illuminate\Http\Client\Response  $response
     * @return void
     */
    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }
}
