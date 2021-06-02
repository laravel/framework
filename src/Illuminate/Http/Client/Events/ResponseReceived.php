<?php

namespace Illuminate\Http\Client\Events;

use Illuminate\Http\Client\Response;

class ResponseReceived
{
    /**
     * The Response object returned from the HTTP request.
     *
     * @var \Illuminate\Http\Client\Response
     */
    public $response;

    public function __construct(Response $response)
    {
        $this->response = $response;
    }
}
