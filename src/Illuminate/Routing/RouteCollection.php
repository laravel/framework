<?php

namespace Illuminate\Routing;

use ArrayIterator;
use Countable;
use Illuminate\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use IteratorAggregate;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Matcher\CompiledUrlMatcher;
use Symfony\Component\Routing\Matcher\Dumper\CompiledUrlMatcherDumper;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection as SymfonyRouteCollection;

class RouteCollection implements Countable, IteratorAggregate
{
    /**
     * An array of the routes keyed by method.
     *
     * @var array
     */
    protected $routes = [];

    /**
     * An array of the compiled Symfony routes.
     *
     * @var array
     */
    protected $compiledRoutes = [];

    /**
     * A flattened array of all of the routes.
     *
     * @var array
     */
    protected $allRoutes = [];

    /**
     * A look-up table of routes by their names.
     *
     * @var array
     */
    protected $nameList = [];

    /**
     * A look-up table of routes by controller action.
     *
     * @var array
     */
    protected $actionList = [];

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
     * Add a Route instance to the collection.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return \Illuminate\Routing\Route
     */
    public function add(Route $route)
    {
        $this->addToCollections($route);

        $this->addLookups($route);

        return $route;
    }

    /**
     * Add the given route to the arrays of routes.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return void
     */
    protected function addToCollections($route)
    {
        $domainAndUri = $route->getDomain().$route->uri();

        foreach ($route->methods() as $method) {
            $this->routes[$method][$domainAndUri] = $route;
        }

        $this->allRoutes[$method.$domainAndUri] = $route;
    }

    /**
     * Add the route to any look-up tables if necessary.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return void
     */
    protected function addLookups($route)
    {
        // If the route has a name, we will add it to the name look-up table so that we
        // will quickly be able to find any route associate with a name and not have
        // to iterate through every route every time we need to perform a look-up.
        if ($name = $route->getName()) {
            $this->nameList[$name] = $route;
        }

        // When the route is routing to a controller we will also store the action that
        // is used by the route. This will let us reverse route to controllers while
        // processing a request and easily generate URLs to the given controllers.
        $action = $route->getAction();

        if (isset($action['controller'])) {
            $this->addToActionList($action, $route);
        }
    }

    /**
     * Add a route to the controller action dictionary.
     *
     * @param  array  $action
     * @param  \Illuminate\Routing\Route  $route
     * @return void
     */
    protected function addToActionList($action, $route)
    {
        $this->actionList[trim($action['controller'], '\\')] = $route;
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
        $this->resolveCompiledRoutes();

        $this->nameList = [];

        foreach ($this->allRoutes as $route) {
            if ($route->getName()) {
                $this->nameList[$route->getName()] = $route;
            }
        }
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
        $this->resolveCompiledRoutes();

        $this->actionList = [];

        foreach ($this->allRoutes as $route) {
            if (isset($route->getAction()['controller'])) {
                $this->addToActionList($route->getAction(), $route);
            }
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
        if ($this->compiledRoutes) {
            $route = $this->matchAgainstCompiledRoutes($request);
        } else {
            $routes = $this->get($request->getMethod());

            // First, we will see if we can find a matching route for this current request
            // method. If we can, great, we can just return it so that it can be called
            // by the consumer. Otherwise we will check for routes with another verb.
            $route = $this->matchAgainstRoutes($routes, $request);
        }

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
     * @param  \Illuminate\Http\Request  $request
     * @param  bool  $includingMethod
     * @return \Illuminate\Routing\Route|null
     */
    protected function matchAgainstRoutes(array $routes, $request, $includingMethod = true)
    {
        [$fallbacks, $routes] = collect($routes)->partition(function ($route) {
            return $route->isFallback;
        });

        return $routes->merge($fallbacks)->first(function (Route $route) use ($request, $includingMethod) {
            return $route->matches($request, $includingMethod);
        });
    }

    /**
     * Determine if a route in the compiled array matches the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Routing\Route|null
     */
    protected function matchAgainstCompiledRoutes($request)
    {
        $context = (new RequestContext())->fromRequest($request);
        $matcher = new CompiledUrlMatcher($this->compiledRoutes['compiled'], $context);

        if ($result = $matcher->matchRequest($request)) {
            return $this->attributesToRoute(
                $this->compiledRoutes['attributes'][$result['_route']]
            );
        }
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
        if ($request->method() === 'OPTIONS') {
            return (new Route('OPTIONS', $request->path(), function () use ($methods) {
                return new Response('', 200, ['Allow' => implode(',', $methods)]);
            }))->bind($request);
        }

        $this->methodNotAllowed($methods, $request->method());
    }

    /**
     * Throw a method not allowed HTTP exception.
     *
     * @param  array  $others
     * @param  string  $method
     * @return void
     *
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    protected function methodNotAllowed(array $others, $method)
    {
        throw new MethodNotAllowedHttpException(
            $others,
            sprintf(
                'The %s method is not supported for this route. Supported methods: %s.',
                $method,
                implode(', ', $others)
            )
        );
    }

    /**
     * Get routes from the collection by method.
     *
     * @param  string|null  $method
     * @return array
     */
    public function get($method = null)
    {
        $this->resolveCompiledRoutes();

        return is_null($method) ? $this->getRoutes() : Arr::get($this->routes, $method, []);
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
        $this->resolveCompiledRoutes();

        return $this->nameList[$name] ?? null;
    }

    /**
     * Get a route instance by its controller action.
     *
     * @param  string  $action
     * @return \Illuminate\Routing\Route|null
     */
    public function getByAction($action)
    {
        $this->resolveCompiledRoutes();

        return $this->actionList[$action] ?? null;
    }

    /**
     * Get all of the routes in the collection.
     *
     * @return array
     */
    public function getRoutes()
    {
        $this->resolveCompiledRoutes();

        return array_values($this->allRoutes);
    }

    /**
     * Get all of the routes keyed by their HTTP verb / method.
     *
     * @return array
     */
    public function getRoutesByMethod()
    {
        $this->resolveCompiledRoutes();

        return $this->routes;
    }

    /**
     * Get all of the routes keyed by their name.
     *
     * @return array
     */
    public function getRoutesByName()
    {
        $this->resolveCompiledRoutes();

        return $this->nameList;
    }

    /**
     * Set the compiled routes from the cached file.
     *
     * @param  array  $compiledRoutes
     * @return void
     */
    public function setCompiledRoutes(array $compiledRoutes)
    {
        $this->compiledRoutes = $compiledRoutes;
    }

    /**
     * Get an iterator for the items.
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->getRoutes());
    }

    /**
     * Count the number of items in the collection.
     *
     * @return int
     */
    public function count()
    {
        return count($this->getRoutes());
    }

    /**
     * Compile the routes for caching.
     *
     * @return array
     */
    public function compile()
    {
        $compiled = $this->dumper()->getCompiledRoutes();

        $attributes = [];

        foreach ($this->getRoutes() as $route) {
            $attributes[$route->getName()] = [
                'methods' => $route->methods(),
                'uri' => $route->uri(),
                'action' => $route->getAction(),
            ];
        }

        return compact('compiled', 'attributes');
    }

    /**
     * Return the CompiledUrlMatcherDumper instance for the route collection.
     *
     * @return \Symfony\Component\Routing\Matcher\Dumper\CompiledUrlMatcherDumper
     */
    public function dumper()
    {
        return new CompiledUrlMatcherDumper($this->toSymfonyRouteCollection());
    }

    /**
     * Resolve the compiled routes to Route instances.
     *
     * @return void
     */
    protected function resolveCompiledRoutes()
    {
        if (empty($this->routes) && $this->compiledRoutes) {
            foreach ($this->compiledRoutes['attributes'] as $name => $attributes) {
                $this->add($this->attributesToRoute($attributes));
            }

            $this->compiledRoutes = [];
        }
    }

    /**
     * Resolve an array of attributes to a Route instance.
     *
     * @param  array  $attributes
     * @return \Illuminate\Routing\Route
     */
    protected function attributesToRoute(array $attributes)
    {
        return (new Route($attributes['methods'], $attributes['uri'], $attributes['action']))
            ->setRouter($this->router)
            ->setContainer($this->container);
    }

    /**
     * Convert the collection to a Symfony RouteCollection instance.
     *
     * @return \Symfony\Component\Routing\RouteCollection
     */
    public function toSymfonyRouteCollection()
    {
        $symfonyRoutes = new SymfonyRouteCollection();

        foreach ($this->getRoutes() as $route) {
            // If the route doesn't have a name, we'll generate one for it
            // and re-add the route to the collection. This way we can
            // add the route to the Symfony route collection.
            if (! $name = $route->getName()) {
                $route->name($name = 'generated::'.Str::random());

                $this->add($route);
            }

            $symfonyRoutes->add($name, $route->toSymfonyRoute());
        }

        $this->refreshNameLookups();

        return $symfonyRoutes;
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
