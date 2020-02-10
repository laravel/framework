<?php

namespace Illuminate\Http\Client;

class RequestException extends Exception
{
    /**
     * The response instance.
     *
     * @var \Illuminate\Http\Client\Response
     */
    public $response;

    /**
     * Create a new exception instance.
     *
     * @param  \Illuminate\Http\Client\Response  $response
     * @return void
     */
    public function __construct(Respnose $response)
    {
        $this->response = $response;
    }
}
