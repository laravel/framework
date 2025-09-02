<?php

namespace Illuminate\Routing\Contracts;

use Illuminate\Routing\Route;

interface ControllerDispatcher
{
    /**
     * Dispatch a request to a given controller and method.
     *
     * @param  string  $method
     */
    public function dispatch(Route $route, $controller, $method);

    /**
     * Get the middleware for the controller instance.
     *
     * @param  \Illuminate\Routing\Controller  $controller
     * @param  string  $method
     * @return array
     */
    public function getMiddleware($controller, $method);
}
