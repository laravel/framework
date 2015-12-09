<?php

namespace Illuminate\Routing;

use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Collection;
use Illuminate\Container\Container;

class ControllerDispatcher
{
    use RouteDependencyResolverTrait;

    /**
     * The router instance.
     *
     * @var \Illuminate\Routing\Router
     */
    protected $router;

    /**
     * The IoC container instance.
     *
     * @var \Illuminate\Container\Container
     */
    protected $container;

    /**
     * Create a new controller dispatcher instance.
     *
     * @param  \Illuminate\Routing\Router  $router
     * @param  \Illuminate\Container\Container  $container
     * @return void
     */
    public function __construct(Router $router,
                                Container $container = null)
    {
        $this->router = $router;
        $this->container = $container;
    }

    /**
     * Dispatch a request to a given controller and method.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $controller
     * @param  string  $method
     * @return mixed
     */
    public function dispatch(Route $route, Request $request, $controller, $method)
    {
        $instance = $this->makeController($controller);

        return $this->callWithinStack($instance, $route, $request, $method);
    }

    /**
     * Make a controller instance via the IoC container.
     *
     * @param  string  $controller
     * @return mixed
     */
    protected function makeController($controller)
    {
        Controller::setRouter($this->router);

        return $this->container->make($controller);
    }

    /**
     * Call the given controller instance method.
     *
     * @param  \Illuminate\Routing\Controller  $instance
     * @param  \Illuminate\Routing\Route  $route
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $method
     * @return mixed
     */
    protected function callWithinStack($instance, $route, $request, $method)
    {
        $middleware = $this->getMiddleware($instance, $method);

        $shouldSkipMiddleware = $this->container->bound('middleware.disable') &&
                                $this->container->make('middleware.disable') === true;

        return (new Pipeline($this->container))
                    ->send($request)
                    ->through($shouldSkipMiddleware ? [] : $middleware)
                    ->then(function ($request) use ($instance, $route, $method) {
                        return $this->router->prepareResponse(
                            $request, $this->call($instance, $route, $method)
                        );
                    });
    }

    /**
     * Get the middleware for the controller instance.
     *
     * @param  \Illuminate\Routing\Controller  $instance
     * @param  string  $method
     * @return array
     */
    protected function getMiddleware($instance, $method)
    {
        $results = new Collection;

        foreach ($instance->getMiddleware() as $name => $options) {
            if (! $this->methodExcludedByOptions($method, $options)) {
                $results[] = $this->router->resolveMiddlewareClassName($name);
            }
        }

        return $results->flatten()->all();
    }

    /**
     * Determine if the given options exclude a particular method.
     *
     * @param  string  $method
     * @param  array  $options
     * @return bool
     */
    public function methodExcludedByOptions($method, array $options)
    {
        return (isset($options['only']) && ! in_array($method, (array) $options['only'])) ||
            (! empty($options['except']) && in_array($method, (array) $options['except']));
    }

    /**
     * Call the given controller instance method.
     *
     * @param  \Illuminate\Routing\Controller  $instance
     * @param  \Illuminate\Routing\Route  $route
     * @param  string  $method
     * @return mixed
     */
    protected function call($instance, $route, $method)
    {
        $parameters = $this->resolveClassMethodDependencies(
            $route->parametersWithoutNulls(), $instance, $method
        );

        return $instance->callAction($method, $parameters);
    }
}
