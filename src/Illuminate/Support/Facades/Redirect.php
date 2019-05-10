<?php

namespace Illuminate\Support\Facades;

/**
 * @method static \Illuminate\Http\RedirectResponse home(int $status = \Illuminate\Http\Response::HTTP_FOUND)
 * @method static \Illuminate\Http\RedirectResponse back(int $status = \Illuminate\Http\Response::HTTP_FOUND, array $headers = [], $fallback = false)
 * @method static \Illuminate\Http\RedirectResponse refresh(int $status = \Illuminate\Http\Response::HTTP_FOUND, array $headers = [])
 * @method static \Illuminate\Http\RedirectResponse guest(string $path, int $status = \Illuminate\Http\Response::HTTP_FOUND, array $headers = [], bool $secure = null)
 * @method static intended(string $default = '/', int $status = \Illuminate\Http\Response::HTTP_FOUND, array $headers = [], bool $secure = null)
 * @method static \Illuminate\Http\RedirectResponse to(string $path, int $status = \Illuminate\Http\Response::HTTP_FOUND, array $headers = [], bool $secure = null)
 * @method static \Illuminate\Http\RedirectResponse away(string $path, int $status = \Illuminate\Http\Response::HTTP_FOUND, array $headers = [])
 * @method static \Illuminate\Http\RedirectResponse secure(string $path, int $status = \Illuminate\Http\Response::HTTP_FOUND, array $headers = [])
 * @method static \Illuminate\Http\RedirectResponse route(string $route, array $parameters = [], int $status = \Illuminate\Http\Response::HTTP_FOUND, array $headers = [])
 * @method static \Illuminate\Http\RedirectResponse action(string $action, array $parameters = [], int $status = \Illuminate\Http\Response::HTTP_FOUND, array $headers = [])
 * @method static \Illuminate\Routing\UrlGenerator getUrlGenerator()
 * @method static void setSession(\Illuminate\Session\Store $session)
 *
 * @see \Illuminate\Routing\Redirector
 */
class Redirect extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'redirect';
    }
}
