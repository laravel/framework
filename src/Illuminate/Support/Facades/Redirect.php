<?php

namespace Illuminate\Support\Facades;

/**
 * @method static \Illuminate\Http\RedirectResponse home(int $status) Create a new redirect response to the "home" route.
 * @method static \Illuminate\Http\RedirectResponse back(int $status, array $headers, mixed $fallback) Create a new redirect response to the previous location.
 * @method static \Illuminate\Http\RedirectResponse refresh(int $status, array $headers) Create a new redirect response to the current URI.
 * @method static \Illuminate\Http\RedirectResponse guest(string $path, int $status, array $headers, bool $secure) Create a new redirect response, while putting the current URL in the session.
 * @method static \Illuminate\Http\RedirectResponse intended(string $default, int $status, array $headers, bool $secure) Create a new redirect response to the previously intended location.
 * @method static \Illuminate\Http\RedirectResponse to(string $path, int $status, array $headers, bool $secure) Create a new redirect response to the given path.
 * @method static \Illuminate\Http\RedirectResponse away(string $path, int $status, array $headers) Create a new redirect response to an external URL (no validation).
 * @method static \Illuminate\Http\RedirectResponse secure(string $path, int $status, array $headers) Create a new redirect response to the given HTTPS path.
 * @method static \Illuminate\Http\RedirectResponse route(string $route, array $parameters, int $status, array $headers) Create a new redirect response to a named route.
 * @method static \Illuminate\Http\RedirectResponse action(string $action, array $parameters, int $status, array $headers) Create a new redirect response to a controller action.
 * @method static \Illuminate\Routing\UrlGenerator getUrlGenerator() Get the URL generator instance.
 * @method static void setSession(\Illuminate\Session\Store $session) Set the active session store.
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
