<?php

namespace Illuminate\Foundation\Http\Events;

class RequestStartHandling
{
    /**
     * The request instance.
     *
     * @var \Illuminate\Http\Request
     */
    public $request;

    /**
     * RequestStartHandling constructor.
     *
     * @param $request
     */
    public function __construct($request)
    {
        $this->request = $request;
    }
}
