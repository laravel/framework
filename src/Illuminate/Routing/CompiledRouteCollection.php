<?php

namespace Illuminate\Routing;

use Illuminate\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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
     * The dynamically added routes that were added after loading the cached, compiled routes.
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
     * A cache of resolved Route instances keyed by route name.
     *
     * @var array<string, \Illuminate\Routing\Route>
     */
    protected $nameCache = [];

    /**
     * A cache of route names grouped by the HTTP method they respond to, built from the route attributes.
     *
     * @var array<string, array<int, string>>|null
     */
    protected $routeNamesByMethod;

    /**
     * A cache of route names keyed by their controller action, built from the route attributes.
     *
     * @var array<string, string>|null
     */
    protected $routeNameByAction;

    /**
     * Create a new CompiledRouteCollection instance.
     *
     * @param  array  $compiled
     * @param  array  $attributes
     */
    public function __construct(array $compiled, array $attributes)
    {
        $this->compiled = $compiled;
        $this->attributes = $attributes;
        $this->routes = new RouteCollection;
    }

    /**
     * Add a Route instance to the collection.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return \Illuminate\Routing\Route
     */
    public function add(Route $route)
    {
        return $this->routes->add($route);
    }

    /**
     * Refresh the name look-up table.
     *
     * This is done in case any names are fluently defined or if routes are overwritten.
     *
     * @return void
     */
    public function refreshNameLookups()
    {
        //
    }

    /**
     * Refresh the action look-up table.
     *
     * This is done in case any actions are overwritten with new controllers.
     *
     * @return void
     */
    public function refreshActionLookups()
    {
        //
    }

    /**
     * Find the first route matching a given request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Routing\Route
     *
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function match(Request $request)
    {
        $matcher = new CompiledUrlMatcher(
            $this->compiled, (new RequestContext)->fromRequest(
                $trimmedRequest = $this->requestWithoutTrailingSlash($request)
            )
        );

        $route = null;

        try {
            if ($result = $matcher->matchRequest($trimmedRequest)) {
                $route = $this->getByName($result['_route']);
            }
        } catch (ResourceNotFoundException|MethodNotAllowedException) {
            try {
                return $this->routes->match($request);
            } catch (NotFoundHttpException) {
                //
            }
        }

        if ($route && $route->isFallback) {
            try {
                $dynamicRoute = $this->routes->match($request);

                if (! $dynamicRoute->isFallback) {
                    $route = $dynamicRoute;
                }
            } catch (NotFoundHttpException|MethodNotAllowedHttpException) {
                //
            }
        }

        return $this->handleMatchedRoute($request, $route);
    }

    /**
     * Get a cloned instance of the given request without any trailing slash on the URI.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Request
     */
    protected function requestWithoutTrailingSlash(Request $request)
    {
        $trimmedRequest = $request->duplicate();

        $parts = explode('?', $request->server->get('REQUEST_URI'), 2);

        $trimmedRequest->server->set(
            'REQUEST_URI', rtrim($parts[0], '/').(isset($parts[1]) ? '?'.$parts[1] : '')
        );

        return $trimmedRequest;
    }

    /**
     * Get routes from the collection by method.
     *
     * @param  string|null  $method
     * @return \Illuminate\Routing\Route[]
     */
    public function get($method = null)
    {
        if (is_null($method)) {
            return $this->getRoutes();
        }

        $routes = (new Collection($this->routeNamesByMethod()[$method] ?? []))
            ->mapWithKeys(function ($name) {
                $route = $this->getByName($name);

                return [$route->getDomain().$route->uri => $route];
            })
            ->all();

        // Dynamically added routes take precedence over cached routes with the same URI...
        return $this->routes->get($method) + $routes;
    }

    /**
     * Determine if the route collection contains a given named route.
     *
     * @param  string  $name
     * @return bool
     */
    public function hasNamedRoute($name)
    {
        return isset($this->attributes[$name]) || $this->routes->hasNamedRoute($name);
    }

    /**
     * Get a route instance by its name.
     *
     * @param  string  $name
     * @return \Illuminate\Routing\Route|null
     */
    public function getByName($name)
    {
        if (isset($this->nameCache[$name])) {
            return $this->nameCache[$name];
        }

        if (isset($this->attributes[$name])) {
            return $this->nameCache[$name] = $this->newRoute($this->attributes[$name]);
        }

        return $this->routes->getByName($name);
    }

    /**
     * Get a route instance by its controller action.
     *
     * @param  string  $action
     * @return \Illuminate\Routing\Route|null
     */
    public function getByAction($action)
    {
        if ($name = $this->routeNameByAction()[$action] ?? null) {
            return $this->getByName($name);
        }

        return $this->routes->getByAction($action);
    }

    /**
     * Get all of the routes in the collection.
     *
     * @return \Illuminate\Routing\Route[]
     */
    public function getRoutes()
    {
        return (new Collection($this->attributes))
            ->map(function (array $attributes) {
                return $this->newRoute($attributes);
            })
            ->merge($this->routes->getRoutes())
            ->values()
            ->all();
    }

    /**
     * Get all of the routes keyed by their HTTP verb / method.
     *
     * @return array
     */
    public function getRoutesByMethod()
    {
        return (new Collection($this->routeNamesByMethod()))
            ->keys()
            ->merge(array_keys($this->routes->getRoutesByMethod()))
            ->unique()
            ->mapWithKeys(fn ($method) => [$method => $this->get($method)])
            ->all();
    }

    /**
     * Get all of the routes keyed by their name.
     *
     * @return \Illuminate\Routing\Route[]
     */
    public function getRoutesByName()
    {
        return (new Collection($this->getRoutes()))
            ->keyBy(function (Route $route) {
                return $route->getName();
            })
            ->all();
    }

    /**
     * Get the cached route names grouped by the HTTP method they respond to.
     *
     * @return array<string, array<int, string>>
     */
    protected function routeNamesByMethod()
    {
        if (! is_null($this->routeNamesByMethod)) {
            return $this->routeNamesByMethod;
        }

        return $this->routeNamesByMethod = (new Collection($this->attributes))
            ->groupBy(fn (array $attributes) => $attributes['methods'], preserveKeys: true)
            ->map(fn (Collection $group) => $group->keys()->all())
            ->all();
    }

    /**
     * Get the cached route names keyed by their controller action.
     *
     * @return array<string, string>
     */
    protected function routeNameByAction()
    {
        if (! is_null($this->routeNameByAction)) {
            return $this->routeNameByAction;
        }

        return $this->routeNameByAction = (new Collection($this->attributes))
            ->map(fn (array $attributes) => isset($attributes['action']['controller'])
                ? trim($attributes['action']['controller'], '\\')
                : ($attributes['action']['uses'] ?? null))
            ->filter(fn ($action) => is_string($action))
            ->reverse()
            ->flip()
            ->all();
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

        return $this->router->newRoute($attributes['methods'], $baseUri === '' ? '/' : $baseUri, $attributes['action'])
            ->setFallback($attributes['fallback'])
            ->setDefaults($attributes['defaults'])
            ->setWheres($attributes['wheres'])
            ->setBindingFields($attributes['bindingFields'])
            ->block($attributes['lockSeconds'] ?? null, $attributes['waitSeconds'] ?? null)
            ->withTrashed($attributes['withTrashed'] ?? false);
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
