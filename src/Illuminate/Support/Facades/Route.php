<?php

namespace Illuminate\Support\Facades;

/**
 * @method static \Illuminate\Routing\Route get(string $uri, \Closure|array|string|callable|null $action = null)
 * @method static \Illuminate\Routing\Route post(string $uri, \Closure|array|string|callable|null $action = null)
 * @method static \Illuminate\Routing\Route put(string $uri, \Closure|array|string|callable|null $action = null)
 * @method static \Illuminate\Routing\Route delete(string $uri, \Closure|array|string|callable|null $action = null)
 * @method static \Illuminate\Routing\Route patch(string $uri, \Closure|array|string|callable|null $action = null)
 * @method static \Illuminate\Routing\Route options(string $uri, \Closure|array|string|callable|null $action = null)
 * @method static \Illuminate\Routing\Route any(string $uri, \Closure|array|string|callable|null $action = null)
 * @method static \Illuminate\Routing\Route match(array|string $methods, string $uri, \Closure|array|string|callable|null $action = null)
 * @method static \Illuminate\Routing\RouteRegistrar prefix(string  $prefix)
 * @method static \Illuminate\Routing\RouteRegistrar where(array  $where)
 * @method static \Illuminate\Routing\PendingResourceRegistration resource(string $name, string $controller, array $options = [])
 * @method static \Illuminate\Routing\PendingResourceRegistration apiResource(string $name, string $controller, array $options = [])
 * @method static void apiResources(array $resources, array $options = [])
 * @method static \Illuminate\Routing\RouteRegistrar middleware(array|string|null $middleware)
 * @method static \Illuminate\Routing\Route substituteBindings(\Illuminate\Support\Facades\Route $route)
 * @method static void substituteImplicitBindings(\Illuminate\Support\Facades\Route $route)
 * @method static \Illuminate\Routing\RouteRegistrar as(string $value)
 * @method static \Illuminate\Routing\RouteRegistrar domain(string $value)
 * @method static \Illuminate\Routing\RouteRegistrar name(string $value)
 * @method static \Illuminate\Routing\RouteRegistrar namespace(string $value)
 * @method static \Illuminate\Routing\Router|\Illuminate\Routing\RouteRegistrar group(\Closure|string|array $attributes, \Closure|string $routes)
 * @method static \Illuminate\Routing\Route redirect(string $uri, string $destination, int $status = 302)
 * @method static \Illuminate\Routing\Route permanentRedirect(string $uri, string $destination)
 * @method static \Illuminate\Routing\Route view(string $uri, string $view, array $data = [])
 * @method static void bind(string $key, string|callable $binder)
 * @method static void model(string $key, string $class, \Closure|null $callback = null)
 * @method static \Illuminate\Routing\Route current()
 * @method static string|null currentRouteName()
 * @method static string|null currentRouteAction()
 *
 * @see \Illuminate\Routing\Router
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
