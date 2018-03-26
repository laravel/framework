<?php

namespace Illuminate\Support\Facades;

/**
 * @method static string current()
 * @method static string to(string $path, $extra = [], bool $secure = null)
 * @method static string secure(string $path, array $parameters = [])
 * @method static string asset(string $path, bool $secure = null)
 * @method static string route(string $name, $parameters = [], bool $absolute = true)
 * @method static string action(string $action, $parameters = [], bool $absolute = true)
 * @method static \Illuminate\Contracts\Routing\UrlGenerator setRootControllerNamespace(string $rootNamespace)
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
