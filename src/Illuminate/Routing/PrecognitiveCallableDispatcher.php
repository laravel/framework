<?php

namespace Illuminate\Routing;

use Illuminate\Container\Container;
use Illuminate\Routing\Contracts\CallableDispatcher as CallableDispatcherContract;
use ReflectionFunction;

class PrecognitiveCallableDispatcher extends CallableDispatcher
{
    /**
     * Dispatch a request to a given callable.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @param  callable  $callable
     * @return mixed
     */
    public function dispatch(Route $route, $callable)
    {
        $this->resolveArguments($route, $callable);

        return $this->container['precongnition.response'];
    }
}
