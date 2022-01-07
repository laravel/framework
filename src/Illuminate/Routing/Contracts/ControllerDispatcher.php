<?php

namespace Illuminate\Routing\Contracts;

use Illuminate\Routing\Route;

interface ControllerDispatcher
{
    /**
     * Dispatch a request to a given controller and method.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @param  mixed  $controller
     * @param  string  $method
     * @return mixed
     */
    public function dispatch(Route $route, $controller, $method);

    /**
     * Get the middleware for the controller instance.
     *
     * @param  string  $controllerClass
     * @param  string  $method
     * @return array
     */
    public function getMiddleware(string $controllerClass, string $method): array;
}
