<?php

namespace Illuminate\Routing;

use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Matcher\CompiledUrlMatcher;
use Symfony\Component\Routing\RequestContext;

class CompiledRouteCollection
{
    /**
     * An array of the compiled Symfony routes.
     *
     * @var array
     */
    protected $compiledRoutes;

    /**
     * An array of the route attributes keyed by route name.
     *
     * @var array
     */
    protected $attributes;

    /**
     * Create a new CompiledRouteCollection instance.
     *
     * @param  array  $routes
     * @return void
     */
    public function __construct(array $routes)
    {
        $this->compiledRoutes = $routes['compiled'];
        $this->attributes = $routes['attributes'];
    }

    /**
     * Find the first route matching a given request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Routing\Route
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function match(Request $request)
    {
        $context = (new RequestContext())->fromRequest($request);
        $matcher = new CompiledUrlMatcher($this->compiledRoutes, $context);

        if ($attributes = $matcher->matchRequest($request)) {
            $name = $attributes['_route'];
            $uri = $this->attributes[$name]['uri'];
            $action = $this->attributes[$name]['action'];

            $route = new Route([$request->method()], $uri, $action);

            if (! is_null($route)) {
                return $route->bind($request);
            }
        }

        throw new NotFoundHttpException;
    }
}
