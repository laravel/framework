<?php

namespace Illuminate\Routing\Events;

class PreparingResponse
{
    /**
     * The request instance.
     *
     * @var \Symfony\Component\HttpFoundation\Request
     */
    public $request;

    /**
     * The response instance.
     *
     * @var mixed
     */
    public $response;

    /**
     * Create a new event instance.
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @param  mixed  $response
     * @return void
     */
    public function __construct($request, $response)
    {
        $this->request = $request;
        $this->response = $response;
    }
}
