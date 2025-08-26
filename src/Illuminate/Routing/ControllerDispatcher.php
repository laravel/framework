<?php

namespace Illuminate\Routing;

use Illuminate\Container\Container;
use Illuminate\Routing\Contracts\ControllerDispatcher as ControllerDispatcherContract;
use Illuminate\Support\Collection;

class ControllerDispatcher implements ControllerDispatcherContract
{
    use FiltersControllerMiddleware, ResolvesRouteDependencies;

    /**
     * The container instance.
     *
     * @var \Illuminate\Container\Container
     */
    protected $container;

    /**
     * Create a new controller dispatcher instance.
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Dispatch a request to a given controller and method.
     *
     * @param  string  $method
     */
    public function dispatch(Route $route, $controller, $method)
    {
        $parameters = $this->resolveParameters($route, $controller, $method);

        if (method_exists($controller, 'callAction')) {
            return $controller->callAction($method, $parameters);
        }

        return $controller->{$method}(...array_values($parameters));
    }

    /**
     * Resolve the parameters for the controller.
     *
     * @param  string  $method
     * @return array
     */
    protected function resolveParameters(Route $route, $controller, $method)
    {
        return $this->resolveClassMethodDependencies(
            $route->parametersWithoutNulls(), $controller, $method
        );
    }

    /**
     * Get the middleware for the controller instance.
     *
     * @param  \Illuminate\Routing\Controller  $controller
     * @param  string  $method
     * @return array
     */
    public function getMiddleware($controller, $method)
    {
        if (! method_exists($controller, 'getMiddleware')) {
            return [];
        }

        return (new Collection($controller->getMiddleware()))
            ->reject(fn ($data) => static::methodExcludedByOptions($method, $data['options']))
            ->pluck('middleware')
            ->all();
    }
}
