<?php

namespace Illuminate\Support\Facades;

/**
 * @method static string full() Get the full URL for the current request.
 * @method static string current() Get the current URL for the request.
 * @method static string previous(mixed $fallback) Get the URL for the previous request.
 * @method static string to(string $path, mixed $extra, bool | null $secure) Generate an absolute URL to the given path.
 * @method static string secure(string $path, array $parameters) Generate a secure, absolute URL to the given path.
 * @method static string asset(string $path, bool | null $secure) Generate the URL to an application asset.
 * @method static string secureAsset(string $path) Generate the URL to a secure asset.
 * @method static string assetFrom(string $root, string $path, bool | null $secure) Generate the URL to an asset from a custom root domain such as CDN, etc.
 * @method static string formatScheme(bool | null $secure) Get the default scheme for a raw URL.
 * @method static string route(string $name, mixed $parameters, bool $absolute) Get the URL to a named route.
 * @method static string action(string $action, mixed $parameters, bool $absolute) Get the URL to a controller action.
 * @method static array formatParameters(mixed | array $parameters) Format the array of URL parameters.
 * @method static string formatRoot(string $scheme, string $root) Get the base URL for the request.
 * @method static string format(string $root, string $path) Format the given URL segments into a single URL.
 * @method static bool isValidUrl(string $path) Determine if the given path is a valid URL.
 * @method static void defaults(array $defaults) Set the default named parameters used by the URL generator.
 * @method static void forceScheme(string $schema) Force the scheme for URLs.
 * @method static void forceRootUrl(string $root) Set the forced root URL.
 * @method static $this formatHostUsing(\Closure $callback) Set a callback to be used to format the host of generated URLs.
 * @method static $this formatPathUsing(\Closure $callback) Set a callback to be used to format the path of generated URLs.
 * @method static \Closure pathFormatter() Get the path formatter being used by the URL generator.
 * @method static \Illuminate\Http\Request getRequest() Get the request instance.
 * @method static void setRequest(\Illuminate\Http\Request $request) Set the current request instance.
 * @method static $this setRoutes(\Illuminate\Routing\RouteCollection $routes) Set the route collection.
 * @method static $this setSessionResolver(callable $sessionResolver) Set the session resolver for the generator.
 * @method static $this setRootControllerNamespace(string $rootNamespace) Set the root controller namespace.
 * @method static void macro(string $name, object | callable $macro) Register a custom macro.
 * @method static void mixin(object $mixin) Mix another object into the class.
 * @method static bool hasMacro(string $name) Checks if macro is registered.
 *
 * @see \Illuminate\Routing\UrlGenerator
 */
class URL extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'url';
    }
}
