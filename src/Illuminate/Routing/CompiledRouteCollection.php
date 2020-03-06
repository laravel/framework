<?php

namespace Illuminate\Routing;

use Illuminate\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
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
     * An array of the routes that were added after loading the compiled routes.
     *
     * @var \Illuminate\Routing\RouteCollection|null
     */
    protected $routes;

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
        if (! $this->routes) {
            $this->routes = new RouteCollection;

            foreach ($this->mapAttributesToRoutes() as $existingRoute) {
                $this->routes->add($existingRoute);
            }
        }

        return $this->routes->add($route);
    }

    /**
     * Recompile the routes.
     *
     * @return void
     */
    public function recompile()
    {
        if ($this->routes) {
            ['compiled' => $compiled, 'attributes' => $attributes] = $this->routes->compile();

            $this->compiled = $compiled;
            $this->attributes = $attributes;

            $this->routes = null;
        }
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
        if ($this->routes) {
            return $this->routes->match($request);
        }

        $route = null;

        $matcher = new CompiledUrlMatcher(
            $this->compiled, (new RequestContext)->fromRequest($request)
        );

        try {
            if ($result = $matcher->matchRequest($request)) {
                $route = $this->getByName($result['_route']);
            }
        } catch (ResourceNotFoundException | MethodNotAllowedException $e) {
            //
        }

        return $this->handleMatchedRoute($request, $route);
    }

    /**
     * Get routes from the collection by method.
     *
     * @param  string|null  $method
     * @return \Illuminate\Routing\Route[]
     */
    public function get($method = null)
    {
        if ($this->routes) {
            return $this->routes->get($method);
        }

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
        if ($this->routes) {
            return $this->routes->hasNamedRoute($name);
        }

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
        if ($this->routes) {
            return $this->routes->getByName($name);
        }

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
        if ($this->routes) {
            return $this->routes->getByAction($action);
        }

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
        if ($this->routes) {
            return $this->routes->getRoutes();
        }

        return $this->mapAttributesToRoutes()->values()->all();
    }

    /**
     * Get all of the routes keyed by their HTTP verb / method.
     *
     * @return array
     */
    public function getRoutesByMethod()
    {
        if ($this->routes) {
            return $this->routes->getRoutesByMethod();
        }

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
        if ($this->routes) {
            return $this->routes->getRoutesByName();
        }

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
            $prefix = trim($attributes['action']['prefix'], '/');

            $baseUri = trim(implode(
                '/', array_slice(
                    explode('/', trim($attributes['uri'], '/')),
                    count($prefix !== '' ? explode('/', $prefix) : [])
                )
            ), '/');
        }

        return (new Route($attributes['methods'], $baseUri == '' ? '/' : $baseUri, $attributes['action']))
            ->setFallback($attributes['fallback'])
            ->setDefaults($attributes['defaults'])
            ->setWheres($attributes['wheres'])
            ->setBindingFields($attributes['bindingFields'])
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
