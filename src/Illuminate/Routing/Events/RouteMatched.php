<?php

namespace Illuminate\Routing\Events;

class RouteMatched
{
    /**
     * The route instance.
     *
     * @var \Illuminate\Routing\Route
     */
    public $route;

    /**
     * The request instance.
     *
     * @var \Illuminate\Http\Request
     */
    public $request;

    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function __construct($route, $request)
    {
        $this->route = $route;
        $this->request = $request;
    }
}
