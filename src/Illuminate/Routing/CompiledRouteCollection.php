<?php

namespace Illuminate\Routing;

use Illuminate\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
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
            'fallback' => $route->isFallback,
            'defaults' => $route->defaults,
            'wheres' => $route->wheres,
        ];

        $this->compiled = [];

        return $route;
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
        if (empty($this->compiled) && $this->attributes) {
            $this->recompileRoutes();
        }

        $route = null;

        $matcher = new CompiledUrlMatcher(
            $this->compiled, (new RequestContext)->fromRequest($request)
        );

        try {
            if ($result = $matcher->matchRequest($request)) {
                $route = $this->getByName($result['_route']);
            }
        } catch (ResourceNotFoundException $e) {
            //
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
     * @return \Illuminate\Routing\Route[]
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
            return $attributes['action']['controller'] === $action;
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
        return $this->mapAttributesToRoutes()
            ->groupBy(function (Route $route) {
                return $route->methods();
            })
            ->map(function (Collection $routes) {
                return $routes->mapWithKeys(function (Route $route) {
                    return [$route->uri => $route];
                })->all();
            })
            ->all();
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
        if (empty($attributes['action']['prefix'] ?? '')) {
            $baseUri = $attributes['uri'];
        } else {
            $baseUri = trim(implode(
                '/', array_slice(
                    explode('/', trim($attributes['uri'], '/')),
                    count(explode('/', trim($attributes['action']['prefix'], '/')))
                )
            ), '/');
        }

        return (new Route($attributes['methods'], $baseUri == '' ? '/' : $baseUri, $attributes['action']))
            ->setFallback($attributes['fallback'])
            ->setDefaults($attributes['defaults'])
            ->setWheres($attributes['wheres'])
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
