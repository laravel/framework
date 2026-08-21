<?php

namespace Illuminate\Routing\Middleware;

use Closure;
use Illuminate\Container\Container;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Route;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class CacheStaticResponse
{
    /**
     * Get the default options for static route caching.
     *
     * @return array
     */
    public static function defaultOptions()
    {
        return [
            'ttl' => 3600,
            'browser_ttl' => 0,
            'strip_cookies' => null,
            'strip_middleware' => [
                StartSession::class,
                ShareErrorsFromSession::class,
                AddQueuedCookiesToResponse::class,
                PreventRequestForgery::class,
            ],
            'vary' => ['X-Inertia'],
            'cdn_cache_control' => true,
        ];
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle($request, Closure $next)
    {
        return $this->cache($request, $next($request));
    }

    /**
     * Apply static route caching to the given response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function cache($request, Response $response)
    {
        if ($this->shouldBypass($request, $response)) {
            return $response;
        }

        $options = $this->resolveOptions($request);

        $this->stripCookies($response, $options['strip_cookies']);
        $this->removeNoCacheHeaders($response);
        $this->setCacheControl($response, (int) $options['ttl'], (int) $options['browser_ttl']);
        $this->setCdnCacheControl($response, (int) $options['ttl'], (bool) $options['cdn_cache_control']);
        $this->setVary($response, $options['vary']);

        return $response;
    }

    /**
     * Determine if the given route has static caching enabled.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return bool
     */
    public static function routeIsStatic(Route $route)
    {
        return array_key_exists('static_cache', $route->getAction());
    }

    /**
     * Determine if the response should not be made cacheable.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @return bool
     */
    protected function shouldBypass($request, Response $response)
    {
        return $request->headers->has('X-Inertia') ||
            ! $request->isMethodCacheable() ||
            $response instanceof RedirectResponse ||
            ! in_array($response->getStatusCode(), [200, 203, 300, 301, 302, 404, 410], true);
    }

    /**
     * Resolve the options for the current request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function resolveOptions($request)
    {
        $defaults = static::defaultOptions();

        $options = array_replace(
            $defaults,
            $this->configuredOptions(),
            $this->routeOptions($request),
        );

        $options['ttl'] ??= $defaults['ttl'];
        $options['browser_ttl'] ??= $defaults['browser_ttl'];
        $options['strip_middleware'] ??= $defaults['strip_middleware'];
        $options['vary'] ??= $defaults['vary'];
        $options['cdn_cache_control'] ??= $defaults['cdn_cache_control'];

        return $options;
    }

    /**
     * Get the configured static cache options.
     *
     * @return array
     */
    protected function configuredOptions()
    {
        $container = Container::getInstance();

        if (! $container->bound('config')) {
            return [];
        }

        $config = $container->make('config')->get('cache.static', []);

        return is_array($config) ? $config : [];
    }

    /**
     * Get the route-specific static cache options.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function routeOptions($request)
    {
        $route = $request->route();

        if (! $route instanceof Route) {
            return [];
        }

        $options = $route->getAction('static_cache') ?? [];

        return is_array($options) ? $options : [];
    }

    /**
     * Strip configured cookies from the response.
     *
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @param  array|null  $cookies
     * @return void
     */
    protected function stripCookies(Response $response, ?array $cookies)
    {
        if (is_null($cookies)) {
            $response->headers->remove('Set-Cookie');

            return;
        }

        foreach ($cookies as $cookie) {
            $response->headers->removeCookie($cookie);
        }
    }

    /**
     * Remove legacy no-cache headers from the response.
     *
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @return void
     */
    protected function removeNoCacheHeaders(Response $response)
    {
        $response->headers->remove('Pragma');
        $response->headers->remove('Expires');
    }

    /**
     * Set the Cache-Control header for browser and shared caches.
     *
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @param  int  $ttl
     * @param  int  $browserTtl
     * @return void
     */
    protected function setCacheControl(Response $response, int $ttl, int $browserTtl)
    {
        $response->headers->set(
            'Cache-Control',
            'public, max-age='.$browserTtl.', s-maxage='.$ttl,
            true
        );
    }

    /**
     * Set the CDN-Cache-Control header when enabled.
     *
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @param  int  $ttl
     * @param  bool  $enabled
     * @return void
     */
    protected function setCdnCacheControl(Response $response, int $ttl, bool $enabled)
    {
        if ($enabled) {
            $response->headers->set('CDN-Cache-Control', 'public, max-age='.$ttl, true);
        }
    }

    /**
     * Merge the configured Vary headers into the response.
     *
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @param  array  $vary
     * @return void
     */
    protected function setVary(Response $response, array $vary)
    {
        $headers = array_merge(
            $this->parseVaryHeader($response->headers->get('Vary')),
            $vary,
            ['X-Inertia'],
        );

        $response->headers->set('Vary', implode(', ', $this->uniqueVaryHeaders($headers)), true);
    }

    /**
     * Parse a Vary header into individual header names.
     *
     * @param  string|null  $header
     * @return array
     */
    protected function parseVaryHeader($header)
    {
        return is_null($header) ? [] : explode(',', $header);
    }

    /**
     * Deduplicate the given Vary header names.
     *
     * @param  array  $headers
     * @return array
     */
    protected function uniqueVaryHeaders(array $headers)
    {
        $seen = [];
        $unique = [];

        foreach ($headers as $header) {
            $header = trim($header);

            if ($header === '') {
                continue;
            }

            $key = strtolower($header);
            $header = $key === 'x-inertia' ? 'X-Inertia' : $header;

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $unique[] = $header;
        }

        return $unique;
    }
}
