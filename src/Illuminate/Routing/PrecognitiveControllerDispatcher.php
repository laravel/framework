<?php

namespace Illuminate\Routing;

use Illuminate\Support\Str;

class PrecognitiveControllerDispatcher extends ControllerDispatcher
{
    /**
     * Dispatch a request to a given controller and method.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @param  mixed  $controller
     * @param  string  $method
     * @return mixed
     */
    public function dispatch(Route $route, $controller, $method)
    {
        $arguments = $this->resolveArguments($route, $controller, $method);

        $predictiveMethod = 'predict'.Str::studly($method);

        $response = null;

        if (method_exists($controller, $predictiveMethod)) {
            $response = $controller->{$predictiveMethod}(...array_values($arguments));
        }

        return $response ?? $this->container['precognition.response'];
    }
}
