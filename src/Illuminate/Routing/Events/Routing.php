<?php

namespace Illuminate\Routing\Events;

class Routing
{
    /**
     * The request instance.
     *
     * @var \Illuminate\Http\Request
     */
    public $request;

    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function __construct($request)
    {
        $this->request = $request;
    }
}
