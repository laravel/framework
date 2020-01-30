<?php

namespace Illuminate\Routing;

use Illuminate\Container\Container;
use Illuminate\Http\Request;
use Symfony\Component\Routing\Matcher\CompiledUrlMatcher;
use Symfony\Component\Routing\RequestContext;

class CompiledRouteCollection extends AbstractRouteCollection
{
    /**
     * The compiled routes collection.
     *
     * @var array
     */
    protected $compiled = [];

    /**
     * An array of the route attributes keyed by name.
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * The router instance used by the route.
     *
     * @var \Illuminate\Routing\Router
     */
    protected $router;

    /**
     * The container instance used by the route.
     *
     * @var \Illuminate\Container\Container
     */
    protected $container;

    /**
     * Create a new CompiledRouteCollection instance.
     *
     * @param  array  $compiled
     * @param  array  $attributes
     * @return void
     */
    public function __construct(array $compiled, array $attributes)
    {
        $this->compiled = $compiled;
        $this->attributes = $attributes;
    }

    /**
     * Add a Route instance to the collection.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return \Illuminate\Routing\Route
     */
    public function add(Route $route)
    {
        $name = $route->getName() ?: $this->generateRouteName();

        $this->attributes[$name] = [
            'methods' => $route->methods(),
            'uri' => $route->uri(),
            'action' => $route->getAction() + ['as' => $name],
        ];

        // Because we don't want to recompile the routes every time a new one
        // is added, we simply clear the array and let the recompiling be
        // done as soon as we need to perform the matching manually.
        $this->compiled = [];
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
        // If the compiled routes array is empty but we have routes set on the attributes array
        // we'll attempt to recompile the routes first. Because compiled routes will always
        // be set on the first request, this won't affect initial request performance.
        if (empty($this->compiled) && $this->attributes) {
            $this->recompileRoutes();
        }

        $route = null;
        $context = (new RequestContext())->fromRequest($request);
        $matcher = new CompiledUrlMatcher($this->compiled, $context);

        if ($result = $matcher->matchRequest($request)) {
            $route = $this->getByName($result['_route']);
        }

        return $this->handleMatchedRoute($request, $route);
    }

    /**
     * Recompile the routes from the attributes array.
     *
     * @return void
     */
    protected function recompileRoutes()
    {
        $this->compiled = $this->dumper()->getCompiledRoutes();
    }

    /**
     * Get routes from the collection by method.
     *
     * @param  string|null  $method
     * @return array
     */
    public function get($method = null)
    {
        return $this->getRoutesByMethod()[$method] ?? [];
    }

    /**
     * Determine if the route collection contains a given named route.
     *
     * @param  string  $name
     * @return bool
     */
    public function hasNamedRoute($name)
    {
        return isset($this->attributes[$name]);
    }

    /**
     * Get a route instance by its name.
     *
     * @param  string  $name
     * @return \Illuminate\Routing\Route|null
     */
    public function getByName($name)
    {
        return isset($this->attributes[$name]) ? $this->newRoute($this->attributes[$name]) : null;
    }

    /**
     * Get a route instance by its controller action.
     *
     * @param  string  $action
     * @return \Illuminate\Routing\Route|null
     */
    public function getByAction($action)
    {
        $attributes = collect($this->attributes)->first(function (array $attributes) use ($action) {
            return $attributes['controller'] === $action;
        });

        return $attributes ? $this->newRoute($attributes) : null;
    }

    /**
     * Get all of the routes in the collection.
     *
     * @return \Illuminate\Routing\Route[]
     */
    public function getRoutes()
    {
        return $this->mapAttributesToRoutes()->values()->all();
    }

    /**
     * Get all of the routes keyed by their HTTP verb / method.
     *
     * @return array
     */
    public function getRoutesByMethod()
    {
        return $this->mapAttributesToRoutes()->groupBy(function (Route $route) {
            return $route->methods();
        })->all();
    }

    /**
     * Get all of the routes keyed by their name.
     *
     * @return \Illuminate\Routing\Route[]
     */
    public function getRoutesByName()
    {
        return $this->mapAttributesToRoutes()->keyBy(function (Route $route) {
            return $route->getName();
        })->all();
    }

    /**
     * Get all of the routes in the collection.
     *
     * @return \Illuminate\Support\Collection
     */
    public function mapAttributesToRoutes()
    {
        return collect($this->attributes)->map(function (array $attributes) {
            return $this->newRoute($attributes);
        });
    }

    /**
     * Resolve an array of attributes to a Route instance.
     *
     * @param  array  $attributes
     * @return \Illuminate\Routing\Route
     */
    protected function newRoute(array $attributes)
    {
        return (new Route($attributes['methods'], $attributes['uri'], $attributes['action']))
            ->setRouter($this->router)
            ->setContainer($this->container);
    }

    /**
     * Set the router instance on the route.
     *
     * @param  \Illuminate\Routing\Router  $router
     * @return $this
     */
    public function setRouter(Router $router)
    {
        $this->router = $router;

        return $this;
    }

    /**
     * Set the container instance on the route.
     *
     * @param  \Illuminate\Container\Container  $container
     * @return $this
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;

        return $this;
    }
}
