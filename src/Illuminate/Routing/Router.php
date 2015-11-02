<?php

namespace Illuminate\Routing;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Collection;
use Illuminate\Container\Container;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Contracts\Events\Dispatcher;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Illuminate\Contracts\Routing\Registrar as RegistrarContract;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Router implements RegistrarContract
{
    use Macroable;

    /**
     * The event dispatcher instance.
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected $events;

    /**
     * The IoC container instance.
     *
     * @var \Illuminate\Container\Container
     */
    protected $container;

    /**
     * The route collection instance.
     *
     * @var \Illuminate\Routing\RouteCollection
     */
    protected $routes;

    /**
     * The currently dispatched route instance.
     *
     * @var \Illuminate\Routing\Route
     */
    protected $current;

    /**
     * The request currently being dispatched.
     *
     * @var \Illuminate\Http\Request
     */
    protected $currentRequest;

    /**
     * All of the short-hand keys for middlewares.
     *
     * @var array
     */
    protected $middleware = [];

    /**
     * The registered pattern based filters.
     *
     * @var array
     */
    protected $patternFilters = [];

    /**
     * The registered regular expression based filters.
     *
     * @var array
     */
    protected $regexFilters = [];

    /**
     * The registered route value binders.
     *
     * @var array
     */
    protected $binders = [];

    /**
     * The globally available parameter patterns.
     *
     * @var array
     */
    protected $patterns = [];

    /**
     * The route group attribute stack.
     *
     * @var array
     */
    protected $groupStack = [];

    /**
     * All of the verbs supported by the router.
     *
     * @var array
     */
    public static $verbs = ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];

    /**
     * Create a new Router instance.
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @param  \Illuminate\Container\Container  $container
     * @return void
     */
    public function __construct(Dispatcher $events, Container $container = null)
    {
        $this->events = $events;
        $this->routes = new RouteCollection;
        $this->container = $container ?: new Container;
    }

    /**
     * Register a new GET route with the router.
     *
     * @param  string  $uri
     * @param  \Closure|array|string  $action
     * @return \Illuminate\Routing\Route
     */
    public function get($uri, $action)
    {
        return $this->addRoute(['GET', 'HEAD'], $uri, $action);
    }

    /**
     * Register a new POST route with the router.
     *
     * @param  string  $uri
     * @param  \Closure|array|string  $action
     * @return \Illuminate\Routing\Route
     */
    public function post($uri, $action)
    {
        return $this->addRoute('POST', $uri, $action);
    }

    /**
     * Register a new PUT route with the router.
     *
     * @param  string  $uri
     * @param  \Closure|array|string  $action
     * @return \Illuminate\Routing\Route
     */
    public function put($uri, $action)
    {
        return $this->addRoute('PUT', $uri, $action);
    }

    /**
     * Register a new PATCH route with the router.
     *
     * @param  string  $uri
     * @param  \Closure|array|string  $action
     * @return \Illuminate\Routing\Route
     */
    public function patch($uri, $action)
    {
        return $this->addRoute('PATCH', $uri, $action);
    }

    /**
     * Register a new DELETE route with the router.
     *
     * @param  string  $uri
     * @param  \Closure|array|string  $action
     * @return \Illuminate\Routing\Route
     */
    public function delete($uri, $action)
    {
        return $this->addRoute('DELETE', $uri, $action);
    }

    /**
     * Register a new OPTIONS route with the router.
     *
     * @param  string  $uri
     * @param  \Closure|array|string  $action
     * @return \Illuminate\Routing\Route
     */
    public function options($uri, $action)
    {
        return $this->addRoute('OPTIONS', $uri, $action);
    }

    /**
     * Register a new route responding to all verbs.
     *
     * @param  string  $uri
     * @param  \Closure|array|string  $action
     * @return \Illuminate\Routing\Route
     */
    public function any($uri, $action)
    {
        $verbs = ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE'];

        return $this->addRoute($verbs, $uri, $action);
    }

    /**
     * Register a new route with the given verbs.
     *
     * @param  array|string  $methods
     * @param  string  $uri
     * @param  \Closure|array|string  $action
     * @return \Illuminate\Routing\Route
     */
    public function match($methods, $uri, $action)
    {
        return $this->addRoute(array_map('strtoupper', (array) $methods), $uri, $action);
    }

    /**
     * Register an array of controllers with wildcard routing.
     *
     * @param  array  $controllers
     * @return void
     *
     * @deprecated since version 5.1.
     */
    public function controllers(array $controllers)
    {
        foreach ($controllers as $uri => $controller) {
            $this->controller($uri, $controller);
        }
    }

    /**
     * Route a controller to a URI with wildcard routing.
     *
     * @param  string  $uri
     * @param  string  $controller
     * @param  array   $names
     * @return void
     *
     * @deprecated since version 5.1.
     */
    public function controller($uri, $controller, $names = [])
    {
        $prepended = $controller;

        // First, we will check to see if a controller prefix has been registered in
        // the route group. If it has, we will need to prefix it before trying to
        // reflect into the class instance and pull out the method for routing.
        if (! empty($this->groupStack)) {
            $prepended = $this->prependGroupUses($controller);
        }

        $routable = (new ControllerInspector)
                            ->getRoutable($prepended, $uri);

        // When a controller is routed using this method, we use Reflection to parse
        // out all of the routable methods for the controller, then register each
        // route explicitly for the developers, so reverse routing is possible.
        foreach ($routable as $method => $routes) {
            foreach ($routes as $route) {
                $this->registerInspected($route, $controller, $method, $names);
            }
        }

        $this->addFallthroughRoute($controller, $uri);
    }

    /**
     * Register an inspected controller route.
     *
     * @param  array   $route
     * @param  string  $controller
     * @param  string  $method
     * @param  array   $names
     * @return void
     *
     * @deprecated since version 5.1.
     */
    protected function registerInspected($route, $controller, $method, &$names)
    {
        $action = ['uses' => $controller.'@'.$method];

        // If a given controller method has been named, we will assign the name to the
        // controller action array, which provides for a short-cut to method naming
        // so you don't have to define an individual route for these controllers.
        $action['as'] = Arr::get($names, $method);

        $this->{$route['verb']}($route['uri'], $action);
    }

    /**
     * Add a fallthrough route for a controller.
     *
     * @param  string  $controller
     * @param  string  $uri
     * @return void
     *
     * @deprecated since version 5.1.
     */
    protected function addFallthroughRoute($controller, $uri)
    {
        $missing = $this->any($uri.'/{_missing}', $controller.'@missingMethod');

        $missing->where('_missing', '(.*)');
    }

    /**
     * Register an array of resource controllers.
     *
     * @param  array  $resources
     * @return void
     */
    public function resources(array $resources)
    {
        foreach ($resources as $name => $controller) {
            $this->resource($name, $controller);
        }
    }

    /**
     * Route a resource to a controller.
     *
     * @param  string  $name
     * @param  string  $controller
     * @param  array   $options
     * @return void
     */
    public function resource($name, $controller, array $options = [])
    {
        if ($this->container && $this->container->bound('Illuminate\Routing\ResourceRegistrar')) {
            $registrar = $this->container->make('Illuminate\Routing\ResourceRegistrar');
        } else {
            $registrar = new ResourceRegistrar($this);
        }

        $registrar->register($name, $controller, $options);
    }

    /**
     * Create a route group with shared attributes.
     *
     * @param  array     $attributes
     * @param  \Closure  $callback
     * @return void
     */
    public function group(array $attributes, Closure $callback)
    {
        $this->updateGroupStack($attributes);

        // Once we have updated the group stack, we will execute the user Closure and
        // merge in the groups attributes when the route is created. After we have
        // run the callback, we will pop the attributes off of this group stack.
        call_user_func($callback, $this);

        array_pop($this->groupStack);
    }

    /**
     * Update the group stack with the given attributes.
     *
     * @param  array  $attributes
     * @return void
     */
    protected function updateGroupStack(array $attributes)
    {
        if (! empty($this->groupStack)) {
            $attributes = $this->mergeGroup($attributes, end($this->groupStack));
        }

        $this->groupStack[] = $attributes;
    }

    /**
     * Merge the given array with the last group stack.
     *
     * @param  array  $new
     * @return array
     */
    public function mergeWithLastGroup($new)
    {
        return $this->mergeGroup($new, end($this->groupStack));
    }

    /**
     * Merge the given group attributes.
     *
     * @param  array  $new
     * @param  array  $old
     * @return array
     */
    public static function mergeGroup($new, $old)
    {
        $new['namespace'] = static::formatUsesPrefix($new, $old);

        $new['prefix'] = static::formatGroupPrefix($new, $old);

        if (isset($new['domain'])) {
            unset($old['domain']);
        }

        $new['where'] = array_merge(
            isset($old['where']) ? $old['where'] : [],
            isset($new['where']) ? $new['where'] : []
        );

        if (isset($old['as'])) {
            $new['as'] = $old['as'].(isset($new['as']) ? $new['as'] : '');
        }

        return array_merge_recursive(array_except($old, ['namespace', 'prefix', 'where', 'as']), $new);
    }

    /**
     * Format the uses prefix for the new group attributes.
     *
     * @param  array  $new
     * @param  array  $old
     * @return string|null
     */
    protected static function formatUsesPrefix($new, $old)
    {
        if (isset($new['namespace'])) {
            return isset($old['namespace'])
                    ? trim($old['namespace'], '\\').'\\'.trim($new['namespace'], '\\')
                    : trim($new['namespace'], '\\');
        }

        return isset($old['namespace']) ? $old['namespace'] : null;
    }

    /**
     * Format the prefix for the new group attributes.
     *
     * @param  array  $new
     * @param  array  $old
     * @return string|null
     */
    protected static function formatGroupPrefix($new, $old)
    {
        $oldPrefix = isset($old['prefix']) ? $old['prefix'] : null;

        if (isset($new['prefix'])) {
            return trim($oldPrefix, '/').'/'.trim($new['prefix'], '/');
        }

        return $oldPrefix;
    }

    /**
     * Get the prefix from the last group on the stack.
     *
     * @return string
     */
    public function getLastGroupPrefix()
    {
        if (! empty($this->groupStack)) {
            $last = end($this->groupStack);

            return isset($last['prefix']) ? $last['prefix'] : '';
        }

        return '';
    }

    /**
     * Add a route to the underlying route collection.
     *
     * @param  array|string  $methods
     * @param  string  $uri
     * @param  \Closure|array|string  $action
     * @return \Illuminate\Routing\Route
     */
    protected function addRoute($methods, $uri, $action)
    {
        return $this->routes->add($this->createRoute($methods, $uri, $action));
    }

    /**
     * Create a new route instance.
     *
     * @param  array|string  $methods
     * @param  string  $uri
     * @param  mixed   $action
     * @return \Illuminate\Routing\Route
     */
    protected function createRoute($methods, $uri, $action)
    {
        // If the route is routing to a controller we will parse the route action into
        // an acceptable array format before registering it and creating this route
        // instance itself. We need to build the Closure that will call this out.
        if ($this->actionReferencesController($action)) {
            $action = $this->convertToControllerAction($action);
        }

        $route = $this->newRoute(
            $methods, $this->prefix($uri), $action
        );

        // If we have groups that need to be merged, we will merge them now after this
        // route has already been created and is ready to go. After we're done with
        // the merge we will be ready to return the route back out to the caller.
        if ($this->hasGroupStack()) {
            $this->mergeGroupAttributesIntoRoute($route);
        }

        $this->addWhereClausesToRoute($route);

        return $route;
    }

    /**
     * Create a new Route object.
     *
     * @param  array|string  $methods
     * @param  string  $uri
     * @param  mixed   $action
     * @return \Illuminate\Routing\Route
     */
    protected function newRoute($methods, $uri, $action)
    {
        return (new Route($methods, $uri, $action))->setContainer($this->container);
    }

    /**
     * Prefix the given URI with the last prefix.
     *
     * @param  string  $uri
     * @return string
     */
    protected function prefix($uri)
    {
        return trim(trim($this->getLastGroupPrefix(), '/').'/'.trim($uri, '/'), '/') ?: '/';
    }

    /**
     * Add the necessary where clauses to the route based on its initial registration.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return \Illuminate\Routing\Route
     */
    protected function addWhereClausesToRoute($route)
    {
        $where = isset($route->getAction()['where']) ? $route->getAction()['where'] : [];

        $route->where(array_merge($this->patterns, $where));

        return $route;
    }

    /**
     * Merge the group stack with the controller action.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return void
     */
    protected function mergeGroupAttributesIntoRoute($route)
    {
        $action = $this->mergeWithLastGroup($route->getAction());

        $route->setAction($action);
    }

    /**
     * Determine if the action is routing to a controller.
     *
     * @param  array  $action
     * @return bool
     */
    protected function actionReferencesController($action)
    {
        if ($action instanceof Closure) {
            return false;
        }

        return is_string($action) || is_string(isset($action['uses']) ? $action['uses'] : null);
    }

    /**
     * Add a controller based route action to the action array.
     *
     * @param  array|string  $action
     * @return array
     */
    protected function convertToControllerAction($action)
    {
        if (is_string($action)) {
            $action = ['uses' => $action];
        }

        // Here we'll merge any group "uses" statement if necessary so that the action
        // has the proper clause for this property. Then we can simply set the name
        // of the controller on the action and return the action array for usage.
        if (! empty($this->groupStack)) {
            $action['uses'] = $this->prependGroupUses($action['uses']);
        }

        // Here we will set this controller name on the action array just so we always
        // have a copy of it for reference if we need it. This can be used while we
        // search for a controller name or do some other type of fetch operation.
        $action['controller'] = $action['uses'];

        return $action;
    }

    /**
     * Prepend the last group uses onto the use clause.
     *
     * @param  string  $uses
     * @return string
     */
    protected function prependGroupUses($uses)
    {
        $group = end($this->groupStack);

        return isset($group['namespace']) && strpos($uses, '\\') !== 0 ? $group['namespace'].'\\'.$uses : $uses;
    }

    /**
     * Dispatch the request to the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function dispatch(Request $request)
    {
        $this->currentRequest = $request;

        // If no response was returned from the before filter, we will call the proper
        // route instance to get the response. If no route is found a response will
        // still get returned based on why no routes were found for this request.
        $response = $this->callFilter('before', $request);

        if (is_null($response)) {
            $response = $this->dispatchToRoute($request);
        }

        // Once this route has run and the response has been prepared, we will run the
        // after filter to do any last work on the response or for this application
        // before we will return the response back to the consuming code for use.
        $response = $this->prepareResponse($request, $response);

        $this->callFilter('after', $request, $response);

        return $response;
    }

    /**
     * Dispatch the request to a route and return the response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function dispatchToRoute(Request $request)
    {
        // First we will find a route that matches this request. We will also set the
        // route resolver on the request so middlewares assigned to the route will
        // receive access to this route instance for checking of the parameters.
        $route = $this->findRoute($request);

        $request->setRouteResolver(function () use ($route) {
            return $route;
        });

        $this->events->fire('router.matched', [$route, $request]);

        // Once we have successfully matched the incoming request to a given route we
        // can call the before filters on that route. This works similar to global
        // filters in that if a response is returned we will not call the route.
        $response = $this->callRouteBefore($route, $request);

        if (is_null($response)) {
            $response = $this->runRouteWithinStack(
                $route, $request
            );
        }

        $response = $this->prepareResponse($request, $response);

        // After we have a prepared response from the route or filter we will call to
        // the "after" filters to do any last minute processing on this request or
        // response object before the response is returned back to the consumer.
        $this->callRouteAfter($route, $request, $response);

        return $response;
    }

    /**
     * Run the given route within a Stack "onion" instance.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    protected function runRouteWithinStack(Route $route, Request $request)
    {
        $shouldSkipMiddleware = $this->container->bound('middleware.disable') &&
                                $this->container->make('middleware.disable') === true;

        $middleware = $shouldSkipMiddleware ? [] : $this->gatherRouteMiddlewares($route);

        return (new Pipeline($this->container))
                        ->send($request)
                        ->through($middleware)
                        ->then(function ($request) use ($route) {
                            return $this->prepareResponse(
                                $request,
                                $route->run($request)
                            );
                        });
    }

    /**
     * Gather the middleware for the given route.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return array
     */
    public function gatherRouteMiddlewares(Route $route)
    {
        return Collection::make($route->middleware())->map(function ($name) {
            return Collection::make($this->resolveMiddlewareClassName($name));
        })
        ->collapse()->all();
    }

    /**
     * Resolve the middleware name to a class name preserving passed parameters.
     *
     * @param  string  $name
     * @return string
     */
    public function resolveMiddlewareClassName($name)
    {
        $map = $this->middleware;

        list($name, $parameters) = array_pad(explode(':', $name, 2), 2, null);

        return (isset($map[$name]) ? $map[$name] : $name).($parameters !== null ? ':'.$parameters : '');
    }

    /**
     * Find the route matching a given request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Routing\Route
     */
    protected function findRoute($request)
    {
        $this->current = $route = $this->routes->match($request);

        $this->container->instance('Illuminate\Routing\Route', $route);

        return $this->substituteBindings($route);
    }

    /**
     * Substitute the route bindings onto the route.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return \Illuminate\Routing\Route
     */
    protected function substituteBindings($route)
    {
        foreach ($route->parameters() as $key => $value) {
            if (isset($this->binders[$key])) {
                $route->setParameter($key, $this->performBinding($key, $value, $route));
            }
        }

        return $route;
    }

    /**
     * Call the binding callback for the given key.
     *
     * @param  string  $key
     * @param  string  $value
     * @param  \Illuminate\Routing\Route  $route
     * @return mixed
     */
    protected function performBinding($key, $value, $route)
    {
        return call_user_func($this->binders[$key], $value, $route);
    }

    /**
     * Register a route matched event listener.
     *
     * @param  string|callable  $callback
     * @return void
     */
    public function matched($callback)
    {
        $this->events->listen('router.matched', $callback);
    }

    /**
     * Register a new "before" filter with the router.
     *
     * @param  string|callable  $callback
     * @return void
     *
     * @deprecated since version 5.1.
     */
    public function before($callback)
    {
        $this->addGlobalFilter('before', $callback);
    }

    /**
     * Register a new "after" filter with the router.
     *
     * @param  string|callable  $callback
     * @return void
     *
     * @deprecated since version 5.1.
     */
    public function after($callback)
    {
        $this->addGlobalFilter('after', $callback);
    }

    /**
     * Register a new global filter with the router.
     *
     * @param  string  $filter
     * @param  string|callable   $callback
     * @return void
     */
    protected function addGlobalFilter($filter, $callback)
    {
        $this->events->listen('router.'.$filter, $this->parseFilter($callback));
    }

    /**
     * Get all of the defined middleware short-hand names.
     *
     * @return array
     */
    public function getMiddleware()
    {
        return $this->middleware;
    }

    /**
     * Register a short-hand name for a middleware.
     *
     * @param  string  $name
     * @param  string  $class
     * @return $this
     */
    public function middleware($name, $class)
    {
        $this->middleware[$name] = $class;

        return $this;
    }

    /**
     * Register a new filter with the router.
     *
     * @param  string  $name
     * @param  string|callable  $callback
     * @return void
     *
     * @deprecated since version 5.1.
     */
    public function filter($name, $callback)
    {
        $this->events->listen('router.filter: '.$name, $this->parseFilter($callback));
    }

    /**
     * Parse the registered filter.
     *
     * @param  callable|string  $callback
     * @return mixed
     */
    protected function parseFilter($callback)
    {
        if (is_string($callback) && ! Str::contains($callback, '@')) {
            return $callback.'@filter';
        }

        return $callback;
    }

    /**
     * Register a pattern-based filter with the router.
     *
     * @param  string  $pattern
     * @param  string  $name
     * @param  array|null  $methods
     * @return void
     *
     * @deprecated since version 5.1.
     */
    public function when($pattern, $name, $methods = null)
    {
        if (! is_null($methods)) {
            $methods = array_map('strtoupper', (array) $methods);
        }

        $this->patternFilters[$pattern][] = compact('name', 'methods');
    }

    /**
     * Register a regular expression based filter with the router.
     *
     * @param  string     $pattern
     * @param  string     $name
     * @param  array|null $methods
     * @return void
     *
     * @deprecated since version 5.1.
     */
    public function whenRegex($pattern, $name, $methods = null)
    {
        if (! is_null($methods)) {
            $methods = array_map('strtoupper', (array) $methods);
        }

        $this->regexFilters[$pattern][] = compact('name', 'methods');
    }

    /**
     * Register a model binder for a wildcard.
     *
     * @param  string  $key
     * @param  string  $class
     * @param  \Closure|null  $callback
     * @return void
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function model($key, $class, Closure $callback = null)
    {
        $this->bind($key, function ($value) use ($class, $callback) {
            if (is_null($value)) {
                return;
            }

            // For model binders, we will attempt to retrieve the models using the first
            // method on the model instance. If we cannot retrieve the models we'll
            // throw a not found exception otherwise we will return the instance.
            $instance = $this->container->make($class);

            if ($model = $instance->where($instance->getRouteKeyName(), $value)->first()) {
                return $model;
            }

            // If a callback was supplied to the method we will call that to determine
            // what we should do when the model is not found. This just gives these
            // developer a little greater flexibility to decide what will happen.
            if ($callback instanceof Closure) {
                return call_user_func($callback, $value);
            }

            throw new NotFoundHttpException;
        });
    }

    /**
     * Add a new route parameter binder.
     *
     * @param  string  $key
     * @param  string|callable  $binder
     * @return void
     */
    public function bind($key, $binder)
    {
        if (is_string($binder)) {
            $binder = $this->createClassBinding($binder);
        }

        $this->binders[str_replace('-', '_', $key)] = $binder;
    }

    /**
     * Create a class based binding using the IoC container.
     *
     * @param  string    $binding
     * @return \Closure
     */
    public function createClassBinding($binding)
    {
        return function ($value, $route) use ($binding) {
            // If the binding has an @ sign, we will assume it's being used to delimit
            // the class name from the bind method name. This allows for bindings
            // to run multiple bind methods in a single class for convenience.
            $segments = explode('@', $binding);

            $method = count($segments) == 2 ? $segments[1] : 'bind';

            $callable = [$this->container->make($segments[0]), $method];

            return call_user_func($callable, $value, $route);
        };
    }

    /**
     * Set a global where pattern on all routes.
     *
     * @param  string  $key
     * @param  string  $pattern
     * @return void
     */
    public function pattern($key, $pattern)
    {
        $this->patterns[$key] = $pattern;
    }

    /**
     * Set a group of global where patterns on all routes.
     *
     * @param  array  $patterns
     * @return void
     */
    public function patterns($patterns)
    {
        foreach ($patterns as $key => $pattern) {
            $this->pattern($key, $pattern);
        }
    }

    /**
     * Call the given filter with the request and response.
     *
     * @param  string  $filter
     * @param  \Illuminate\Http\Request   $request
     * @param  \Illuminate\Http\Response  $response
     * @return mixed
     */
    protected function callFilter($filter, $request, $response = null)
    {
        return $this->events->until('router.'.$filter, [$request, $response]);
    }

    /**
     * Call the given route's before filters.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function callRouteBefore($route, $request)
    {
        $response = $this->callPatternFilters($route, $request);

        return $response ?: $this->callAttachedBefores($route, $request);
    }

    /**
     * Call the pattern based filters for the request.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    protected function callPatternFilters($route, $request)
    {
        foreach ($this->findPatternFilters($request) as $filter => $parameters) {
            $response = $this->callRouteFilter($filter, $parameters, $route, $request);

            if (! is_null($response)) {
                return $response;
            }
        }
    }

    /**
     * Find the patterned filters matching a request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     *
     * @deprecated since version 5.1.
     */
    public function findPatternFilters($request)
    {
        $results = [];

        list($path, $method) = [$request->path(), $request->getMethod()];

        foreach ($this->patternFilters as $pattern => $filters) {
            // To find the patterned middlewares for a request, we just need to check these
            // registered patterns against the path info for the current request to this
            // applications, and when it matches we will merge into these middlewares.
            if (Str::is($pattern, $path)) {
                $merge = $this->patternsByMethod($method, $filters);

                $results = array_merge($results, $merge);
            }
        }

        foreach ($this->regexFilters as $pattern => $filters) {
            // To find the patterned middlewares for a request, we just need to check these
            // registered patterns against the path info for the current request to this
            // applications, and when it matches we will merge into these middlewares.
            if (preg_match($pattern, $path)) {
                $merge = $this->patternsByMethod($method, $filters);

                $results = array_merge($results, $merge);
            }
        }

        return $results;
    }

    /**
     * Filter pattern filters that don't apply to the request verb.
     *
     * @param  string  $method
     * @param  array   $filters
     * @return array
     */
    protected function patternsByMethod($method, $filters)
    {
        $results = [];

        foreach ($filters as $filter) {
            // The idea here is to check and see if the pattern filter applies to this HTTP
            // request based on the request methods. Pattern filters might be limited by
            // the request verb to make it simply to assign to the given verb at once.
            if ($this->filterSupportsMethod($filter, $method)) {
                $parsed = Route::parseFilters($filter['name']);

                $results = array_merge($results, $parsed);
            }
        }

        return $results;
    }

    /**
     * Determine if the given pattern filters applies to a given method.
     *
     * @param  array  $filter
     * @param  array  $method
     * @return bool
     */
    protected function filterSupportsMethod($filter, $method)
    {
        $methods = $filter['methods'];

        return is_null($methods) || in_array($method, $methods);
    }

    /**
     * Call the given route's before (non-pattern) filters.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    protected function callAttachedBefores($route, $request)
    {
        foreach ($route->beforeFilters() as $filter => $parameters) {
            $response = $this->callRouteFilter($filter, $parameters, $route, $request);

            if (! is_null($response)) {
                return $response;
            }
        }
    }

    /**
     * Call the given route's after filters.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Http\Response  $response
     * @return mixed
     *
     * @deprecated since version 5.1.
     */
    public function callRouteAfter($route, $request, $response)
    {
        foreach ($route->afterFilters() as $filter => $parameters) {
            $this->callRouteFilter($filter, $parameters, $route, $request, $response);
        }
    }

    /**
     * Call the given route filter.
     *
     * @param  string  $filter
     * @param  array  $parameters
     * @param  \Illuminate\Routing\Route  $route
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Http\Response|null $response
     * @return mixed
     *
     * @deprecated since version 5.1.
     */
    public function callRouteFilter($filter, $parameters, $route, $request, $response = null)
    {
        $data = array_merge([$route, $request, $response], $parameters);

        return $this->events->until('router.filter: '.$filter, $this->cleanFilterParameters($data));
    }

    /**
     * Clean the parameters being passed to a filter callback.
     *
     * @param  array  $parameters
     * @return array
     */
    protected function cleanFilterParameters(array $parameters)
    {
        return array_filter($parameters, function ($p) {
            return ! is_null($p) && $p !== '';
        });
    }

    /**
     * Create a response instance from the given value.
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @param  mixed  $response
     * @return \Illuminate\Http\Response
     */
    public function prepareResponse($request, $response)
    {
        if ($response instanceof PsrResponseInterface) {
            $response = (new HttpFoundationFactory)->createResponse($response);
        } elseif (! $response instanceof SymfonyResponse) {
            $response = new Response($response);
        }

        return $response->prepare($request);
    }

    /**
     * Determine if the router currently has a group stack.
     *
     * @return bool
     */
    public function hasGroupStack()
    {
        return ! empty($this->groupStack);
    }

    /**
     * Get the current group stack for the router.
     *
     * @return array
     */
    public function getGroupStack()
    {
        return $this->groupStack;
    }

    /**
     * Get a route parameter for the current route.
     *
     * @param  string  $key
     * @param  string  $default
     * @return mixed
     */
    public function input($key, $default = null)
    {
        return $this->current()->parameter($key, $default);
    }

    /**
     * Get the currently dispatched route instance.
     *
     * @return \Illuminate\Routing\Route
     */
    public function getCurrentRoute()
    {
        return $this->current();
    }

    /**
     * Get the currently dispatched route instance.
     *
     * @return \Illuminate\Routing\Route
     */
    public function current()
    {
        return $this->current;
    }

    /**
     * Check if a route with the given name exists.
     *
     * @param  string  $name
     * @return bool
     */
    public function has($name)
    {
        return $this->routes->hasNamedRoute($name);
    }

    /**
     * Get the current route name.
     *
     * @return string|null
     */
    public function currentRouteName()
    {
        return $this->current() ? $this->current()->getName() : null;
    }

    /**
     * Alias for the "currentRouteNamed" method.
     *
     * @param  mixed  string
     * @return bool
     */
    public function is()
    {
        foreach (func_get_args() as $pattern) {
            if (Str::is($pattern, $this->currentRouteName())) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the current route matches a given name.
     *
     * @param  string  $name
     * @return bool
     */
    public function currentRouteNamed($name)
    {
        return $this->current() ? $this->current()->getName() == $name : false;
    }

    /**
     * Get the current route action.
     *
     * @return string|null
     */
    public function currentRouteAction()
    {
        if (! $this->current()) {
            return;
        }

        $action = $this->current()->getAction();

        return isset($action['controller']) ? $action['controller'] : null;
    }

    /**
     * Alias for the "currentRouteUses" method.
     *
     * @param  mixed  string
     * @return bool
     */
    public function uses()
    {
        foreach (func_get_args() as $pattern) {
            if (Str::is($pattern, $this->currentRouteAction())) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the current route action matches a given action.
     *
     * @param  string  $action
     * @return bool
     */
    public function currentRouteUses($action)
    {
        return $this->currentRouteAction() == $action;
    }

    /**
     * Get the request currently being dispatched.
     *
     * @return \Illuminate\Http\Request
     */
    public function getCurrentRequest()
    {
        return $this->currentRequest;
    }

    /**
     * Get the underlying route collection.
     *
     * @return \Illuminate\Routing\RouteCollection
     */
    public function getRoutes()
    {
        return $this->routes;
    }

    /**
     * Set the route collection instance.
     *
     * @param  \Illuminate\Routing\RouteCollection  $routes
     * @return void
     */
    public function setRoutes(RouteCollection $routes)
    {
        foreach ($routes as $route) {
            $route->setContainer($this->container);
        }

        $this->routes = $routes;

        $this->container->instance('routes', $this->routes);
    }

    /**
     * Get the global "where" patterns.
     *
     * @return array
     */
    public function getPatterns()
    {
        return $this->patterns;
    }
}
