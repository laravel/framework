<?php

namespace Illuminate\Routing;

use Illuminate\Routing\Contracts\CallableDispatcher as CallableDispatcherContract;
use ReflectionFunction;

class CallableDispatcher implements CallableDispatcherContract
{
    use RouteDependencyResolverTrait;

    /**
     * Dispatch a request to a given callable.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @param  callable  $callable
     * @return mixed
     */
    public function dispatch(Route $route, $callable)
    {
        return $callable(...array_values($this->resolveMethodDependencies(
            $route->parametersWithoutNulls(), new ReflectionFunction($callable)
        )));
    }
}
