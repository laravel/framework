<?php

namespace Illuminate\Routing\Events;

class ResponsePrepared
{
    /**
     * The request object.
     *
     * @var \Illuminate\Http\Request
     */
    public $request;

    /**
     * The response object being resolved.
     *
     * @var \Symfony\Component\HttpFoundation\Response
     */
    public $response;

    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     */
    public function __construct($request, $response)
    {
        $this->request = $request;

        $this->response = $response;
    }
}
