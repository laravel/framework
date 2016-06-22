<?php

namespace Illuminate\Routing;

class ControllerDispatcher
{
    use RouteDependencyResolverTrait;

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
        return $this->call($route, $controller, $method);
    }

    /**
     * Call the given controller instance method.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @param  \Illuminate\Routing\Controller  $controller
     * @param  string  $method
     * @return mixed
     */
    protected function call($route, $controller, $method)
    {
        $parameters = $this->resolveClassMethodDependencies(
            $route->parametersWithoutNulls(), $controller, $method
        );

        if (method_exists($controller, 'callAction')) {
            return $controller->callAction($method, $parameters);
        }

        return call_user_func_array([$controller, $method], $parameters);
    }

    /**
     * Get the middleware for the controller instance.
     *
     * @param  \Illuminate\Routing\Controller  $instance
     * @param  string  $method
     * @return array
     */
    public static function getMiddleware($controller, $method)
    {
        if (! method_exists($controller, 'getMiddleware')) {
            return [];
        }

        return collect($controller->getMiddleware())->reject(function ($options, $name) use ($method) {
            return static::methodExcludedByOptions($method, $options);
        })->keys()->all();
    }

    /**
     * Determine if the given options exclude a particular method.
     *
     * @param  string  $method
     * @param  array  $options
     * @return bool
     */
    protected static function methodExcludedByOptions($method, array $options)
    {
        return (isset($options['only']) && ! in_array($method, (array) $options['only'])) ||
            (! empty($options['except']) && in_array($method, (array) $options['except']));
    }
}
