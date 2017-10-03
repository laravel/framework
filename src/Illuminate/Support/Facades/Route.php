<?php

namespace Illuminate\Support\Facades;

/**
 * @method static \Illuminate\Support\Facades\Route get(string $uri, \Closure | array | string | null $action = null) Register a new GET route with the router.
 * @method static \Illuminate\Support\Facades\Route post(string $uri, \Closure | array | string | null $action = null) Register a new POST route with the router.
 * @method static \Illuminate\Support\Facades\Route put(string $uri, \Closure | array | string | null $action = null) Register a new PUT route with the router.
 * @method static \Illuminate\Support\Facades\Route patch(string $uri, \Closure | array | string | null $action = null) Register a new PATCH route with the router.
 * @method static \Illuminate\Support\Facades\Route delete(string $uri, \Closure | array | string | null $action = null) Register a new DELETE route with the router.
 * @method static \Illuminate\Support\Facades\Route options(string $uri, \Closure | array | string | null $action = null) Register a new OPTIONS route with the router.
 * @method static \Illuminate\Support\Facades\Route any(string $uri, \Closure | array | string | null $action = null) Register a new route responding to all verbs.
 * @method static \Illuminate\Support\Facades\Route fallback(\Closure | array | string | null $action = null) Register a new Fallback route with the router.
 * @method static \Illuminate\Support\Facades\Route redirect(string $uri, string $destination, int $status = 301) Create a redirect from one URI to another.
 * @method static \Illuminate\Support\Facades\Route view(string $uri, string $view, array $data = []) Register a new route that returns a view.
 * @method static \Illuminate\Support\Facades\Route match(array | string $methods, string $uri, \Closure | array | string | null $action = null) Register a new route with the given verbs.
 * @method static void resources(array $resources) Register an array of resource controllers.
 * @method static \Illuminate\Routing\PendingResourceRegistration resource(string $name, string $controller, array $options = []) Route a resource to a controller.
 * @method static \Illuminate\Routing\PendingResourceRegistration apiResource(string $name, string $controller, array $options = []) Route an api resource to a controller.
 * @method static void group(array $attributes, \Closure | string $routes) Create a route group with shared attributes.
 * @method static array mergeWithLastGroup(array $new) Merge the given array with the last group stack.
 * @method static string getLastGroupPrefix() Get the prefix from the last group on the stack.
 * @method static \Illuminate\Http\Response|\Illuminate\Http\JsonResponse dispatch(\Illuminate\Http\Request $request) Dispatch the request to the application.
 * @method static mixed dispatchToRoute(\Illuminate\Http\Request $request) Dispatch the request to a route and return the response.
 * @method static array gatherRouteMiddleware(\Illuminate\Support\Facades\Route $route) Gather the middleware for the given route with resolved class names.
 * @method static \Illuminate\Http\Response|\Illuminate\Http\JsonResponse prepareResponse(\Symfony\Component\HttpFoundation\Request $request, mixed $response) Create a response instance from the given value.
 * @method static \Illuminate\Http\Response|\Illuminate\Http\JsonResponse toResponse(\Symfony\Component\HttpFoundation\Request $request, mixed $response) static version substituteBindings(\Illuminate\Support\Facades\Route $route) Substitute the route bindings onto the route.
 * @method static void substituteImplicitBindings(\Illuminate\Support\Facades\Route $route) Substitute the implicit Eloquent model bindings for the route.
 * @method static void matched(string | callable $callback) Register a route matched event listener.
 * @method static array getMiddleware() Get all of the defined middleware short-hand names.
 * @method static $this aliasMiddleware(string $name, string $class) Register a short-hand name for a middleware.
 * @method static bool hasMiddlewareGroup(string $name) Check if a middlewareGroup with the given name exists.
 * @method static array getMiddlewareGroups() Get all of the defined middleware groups.
 * @method static $this middlewareGroup(string $name, array $middleware) Register a group of middleware.
 * @method static $this prependMiddlewareToGroup(string $group, string $middleware) Add a middleware to the beginning of a middleware group.
 * @method static $this pushMiddlewareToGroup(string $group, string $middleware) Add a middleware to the end of a middleware group.
 * @method static void bind(string $key, string | callable $binder) Add a new route parameter binder.
 * @method static void model(string $key, string $class, \Closure | null $callback) Register a model binder for a wildcard.
 * @method static \Closure|null getBindingCallback(string $key) Get the binding callback for a given binding.
 * @method static array getPatterns() Get the global "where" patterns.
 * @method static void pattern(string $key, string $pattern) Set a global where pattern on all routes.
 * @method static void patterns(array $patterns) Set a group of global where patterns on all routes.
 * @method static bool hasGroupStack() Determine if the router currently has a group stack.
 * @method static array getGroupStack() Get the current group stack for the router.
 * @method static mixed input(string $key, string $default) Get a route parameter for the current route.
 * @method static \Illuminate\Http\Request getCurrentRequest() Get the request currently being dispatched.
 * @method static \Illuminate\Support\Facades\Route getCurrentRoute() Get the currently dispatched route instance.
 * @method static \Illuminate\Support\Facades\Route current() Get the currently dispatched route instance.
 * @method static bool has(string $name) Check if a route with the given name exists.
 * @method static string|null currentRouteName() Get the current route name.
 * @method static bool is(mixed $patterns) Alias for the "currentRouteNamed" method.
 * @method static bool currentRouteNamed(mixed $patterns) Determine if the current route matches a pattern.
 * @method static string|null currentRouteAction() Get the current route action.
 * @method static bool uses(array $patterns) Alias for the "currentRouteUses" method.
 * @method static bool currentRouteUses(string $action) Determine if the current route action matches a given action.
 * @method static void auth() Register the typical authentication routes for an application.
 * @method static void singularResourceParameters(bool $singular) Set the unmapped global resource parameters to singular.
 * @method static void resourceParameters(array $parameters) Set the global resource parameter mapping.
 * @method static array|null resourceVerbs(array $verbs) Get or set the verbs used in the resource URIs.
 * @method static \Illuminate\Routing\RouteCollection getRoutes() Get the underlying route collection.
 * @method static void setRoutes(\Illuminate\Routing\RouteCollection $routes) Set the route collection instance.
 * @method static void macro(string $name, object | callable $macro) Register a custom macro.
 * @method static void mixin(object $mixin) Mix another object into the class.
 * @method static bool hasMacro(string $name) Checks if macro is registered.
 * @method static mixed macroCall(string $method, array $parameters) Dynamically handle calls to the class.
 * @method static \Illuminate\Support\Facades\Route prefix(string $prefix)
 * @method static \Illuminate\Support\Facades\Route middleware(array | string | null $middleware)
 * @method static \Illuminate\Support\Facades\Route substituteBindings(\Illuminate\Support\Facades\Route $route)
 * @method static \Illuminate\Support\Facades\Route as(string $value)
 * @method static \Illuminate\Support\Facades\Route domain(string $value)
 * @method static \Illuminate\Support\Facades\Route name(string $value)
 * @method static \Illuminate\Support\Facades\Route namespace(string $value)
 *
 * @see \Illuminate\Routing\Router
 * @see \Illuminate\Routing\Route
 */
class Route extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'router';
    }
}
