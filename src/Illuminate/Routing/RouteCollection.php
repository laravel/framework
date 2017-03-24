<?php

namespace Illuminate\Routing;

use Countable;
use IteratorAggregate;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class RouteCollection implements Countable, IteratorAggregate
{
    /**
     * The Route collection.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $collection;

    /**
     * Create a new RouteCollection instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->collection = new Collection;
    }

    /**
     * Add a Route instance to the collection.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return \Illuminate\Routing\Route
     */
    public function add(Route $route)
    {
        foreach ($route->methods() as $method) {
            $this->collection->put($method.$route->domain().$route->uri(), $route);
        }

        return $route;
    }

    /**
     * Refresh the name look-up table.
     *
     * This is done in case any names are fluently defined.
     *
     * @deprecated since version 5.5.
     *
     * @return void
     */
    public function refreshNameLookups()
    {
        //
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
        $routes = $this->get($request->getMethod());

        // First, we will see if we can find a matching route for this current request
        // method. If we can, great, we can just return it so that it can be called
        // by the consumer. Otherwise we will check for routes with another verb.
        $route = $this->matchAgainstRoutes($routes, $request);

        if (! is_null($route)) {
            return $route->bind($request);
        }

        // If no route was found we will now check if a matching route is specified by
        // another HTTP verb. If it is we will need to throw a MethodNotAllowed and
        // inform the user agent of which HTTP verb it should use for this route.
        $others = $this->checkForAlternateVerbs($request);

        if (count($others) > 0) {
            return $this->getRouteForMethods($request, $others);
        }

        throw new NotFoundHttpException;
    }

    /**
     * Determine if a route in the array matches the request.
     *
     * @param  array  $routes
     * @param  \Illuminate\http\Request  $request
     * @param  bool  $includingMethod
     * @return \Illuminate\Routing\Route|null
     */
    protected function matchAgainstRoutes(array $routes, $request, $includingMethod = true)
    {
        return Arr::first($routes, function ($value) use ($request, $includingMethod) {
            return $value->matches($request, $includingMethod);
        });
    }

    /**
     * Determine if any routes match on another HTTP verb.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function checkForAlternateVerbs($request)
    {
        $methods = array_diff(Router::$verbs, [$request->getMethod()]);

        // Here we will spin through all verbs except for the current request verb and
        // check to see if any routes respond to them. If they do, we will return a
        // proper error response with the correct headers on the response string.
        $others = [];

        foreach ($methods as $method) {
            if (! is_null($this->matchAgainstRoutes($this->get($method), $request, false))) {
                $others[] = $method;
            }
        }

        return $others;
    }

    /**
     * Get a route (if necessary) that responds when other available methods are present.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  array  $methods
     * @return \Illuminate\Routing\Route
     *
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    protected function getRouteForMethods($request, array $methods)
    {
        if ($request->method() == 'OPTIONS') {
            return (new Route('OPTIONS', $request->path(), function () use ($methods) {
                return new Response('', 200, ['Allow' => implode(',', $methods)]);
            }))->bind($request);
        }

        $this->methodNotAllowed($methods);
    }

    /**
     * Throw a method not allowed HTTP exception.
     *
     * @param  array  $others
     * @return void
     *
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    protected function methodNotAllowed(array $others)
    {
        throw new MethodNotAllowedHttpException($others);
    }

    /**
     * Get routes from the collection by method.
     *
     * @param  string|null  $method
     * @return array
     */
    public function get($method = null)
    {
        return is_null($method) ? $this->getRoutes() : $this->collection->filter(function (Route $route) use ($method) {
            return in_array($method, $route->methods());
        })->toArray();
    }

    /**
     * Determine if the route collection contains a given named route.
     *
     * @param  string  $name
     * @return bool
     */
    public function hasNamedRoute($name)
    {
        return ! is_null($this->getByName($name));
    }

    /**
     * Get a route instance by its name.
     *
     * @param  string  $name
     * @return \Illuminate\Routing\Route|null
     */
    public function getByName($name)
    {
        return $this->collection->first(function (Route $route) use ($name) {
            return Arr::get($route->getAction(), 'as') === $name;
        });
    }

    /**
     * Get a route instance by its controller action.
     *
     * @param  string  $action
     * @return \Illuminate\Routing\Route|null
     */
    public function getByAction($action)
    {
        return $this->collection->first(function (Route $route) use ($action) {
            return trim(Arr::get($route->getAction(), 'controller'), '\\') === $action;
        });
    }

    /**
     * Get all of the routes in the collection.
     *
     * @return array
     */
    public function getRoutes()
    {
        return $this->getUniqueRoutes()->values()->toArray();
    }

    /**
     * Get all of the routes keyed by their HTTP verb / method.
     *
     * @return array
     */
    public function getRoutesByMethod()
    {
        return $this->collection->mapWithKeys(function (Route $route) {
            foreach ($route->methods() as $method) {
                return [$method => [$route->domain().$route->uri() => $route]];
            }
        })->toArray();
    }

    /**
     * Get the unique routes.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getUniqueRoutes()
    {
        return $this->collection->unique(function (Route $route) {
            return Arr::last($route->methods()).$route->domain().$route->uri();
        });
    }

    /**
     * Get an iterator for the items.
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return $this->getUniqueRoutes()->getIterator();
    }

    /**
     * Count the number of items in the collection.
     *
     * @return int
     */
    public function count()
    {
        return $this->getUniqueRoutes()->count();
    }
}
