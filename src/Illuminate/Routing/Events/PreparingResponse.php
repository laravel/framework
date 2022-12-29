<?php

namespace Illuminate\Routing\Events;

class PreparingResponse
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
     * @var mixed
     */
    public $response;

    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $response
     */
    public function __construct($request, $response)
    {
        $this->request = $request;

        $this->response = $response;
    }
}
