<?php

namespace Illuminate\Support\Facades;

/**
 * @method static string action(string|array $action, mixed $parameters = [], bool $absolute = true)
 * @method static string asset(string $path, bool|null $secure = null)
 * @method static string assetFrom(string $root, string $path, bool|null $secure = null)
 * @method static string current()
 * @method static void defaults(array $defaults)
 * @method static void flushMacros()
 * @method static void forceRootUrl(string|null $root)
 * @method static void forceScheme(string|null $scheme)
 * @method static string format(string $root, string $path, \Illuminate\Routing\Route|null $route = null)
 * @method static \Illuminate\Routing\UrlGenerator formatHostUsing(\Closure $callback)
 * @method static array formatParameters(mixed|array $parameters)
 * @method static \Illuminate\Routing\UrlGenerator formatPathUsing(\Closure $callback)
 * @method static string formatRoot(string $scheme, string|null $root = null)
 * @method static string formatScheme(bool|null $secure = null)
 * @method static string full()
 * @method static array getDefaultParameters()
 * @method static \Illuminate\Http\Request getRequest()
 * @method static string getRootControllerNamespace()
 * @method static bool hasCorrectSignature(\Illuminate\Http\Request $request, bool $absolute = true, array $ignoreQuery = [])
 * @method static bool hasMacro(string $name)
 * @method static bool hasValidRelativeSignature(\Illuminate\Http\Request $request, array $ignoreQuery = [])
 * @method static bool hasValidSignature(\Illuminate\Http\Request $request, bool $absolute = true, array $ignoreQuery = [])
 * @method static bool isValidUrl(string $path)
 * @method static void macro(string $name, object|callable $macro)
 * @method static void mixin(object $mixin, bool $replace = true)
 * @method static \Closure pathFormatter()
 * @method static string previous(mixed $fallback = false)
 * @method static string previousPath(mixed $fallback = false)
 * @method static string route(string $name, mixed $parameters = [], bool $absolute = true)
 * @method static string secure(string $path, array $parameters = [])
 * @method static string secureAsset(string $path)
 * @method static \Illuminate\Routing\UrlGenerator setKeyResolver(callable $keyResolver)
 * @method static void setRequest(\Illuminate\Http\Request $request)
 * @method static \Illuminate\Routing\UrlGenerator setRootControllerNamespace(string $rootNamespace)
 * @method static \Illuminate\Routing\UrlGenerator setRoutes(\Illuminate\Routing\RouteCollectionInterface $routes)
 * @method static \Illuminate\Routing\UrlGenerator setSessionResolver(callable $sessionResolver)
 * @method static bool signatureHasNotExpired(\Illuminate\Http\Request $request)
 * @method static string signedRoute(string $name, mixed $parameters = [], \DateTimeInterface|\DateInterval|int|null $expiration = null, bool $absolute = true)
 * @method static string temporarySignedRoute(string $name, \DateTimeInterface|\DateInterval|int $expiration, array $parameters = [], bool $absolute = true)
 * @method static string to(string $path, mixed $extra = [], bool|null $secure = null)
 * @method static string toRoute(\Illuminate\Routing\Route $route, mixed $parameters, bool $absolute)
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
