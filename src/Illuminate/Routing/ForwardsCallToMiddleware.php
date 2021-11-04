<?php

namespace Illuminate\Routing;

use Illuminate\Foundation\Http\Kernel;

trait ForwardsCallToMiddleware
{
    /**
     * Sets the middleware in the route if set in the HTTP Kernel.
     *
     * @param  string  $middleware
     * @param  array  $parameters
     * @return static|void
     */
    protected function forwardToMiddleware($middleware, $parameters)
    {
        return $this->middleware($this->parseMiddlewareFromMethod($middleware, $parameters));
    }

    /**
     * Transforms method call to a middleware declaration.
     *
     * @param  string  $middleware
     * @param  array  $parameters
     * @return string
     */
    protected function parseMiddlewareFromMethod($middleware, $parameters)
    {
        return rtrim($middleware.':'.implode(',', $parameters), ':');
    }

    /**
     * Check if the method name exists as a route middleware key.
     *
     * @param  string  $method
     * @return bool
     */
    protected function existsInRouteMiddleware($method)
    {
        return isset(app(Kernel::class)->getRouteMiddleware()[$method]);
    }
}
