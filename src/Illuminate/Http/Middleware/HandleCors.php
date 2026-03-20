<?php

namespace Illuminate\Http\Middleware;

use Closure;
use Fruitcake\Cors\CorsService;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;

class HandleCors
{
    /**
     * The container instance.
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * The CORS service instance.
     *
     * @var \Fruitcake\Cors\CorsService
     */
    protected $cors;

    /**
     * All of the registered skip callbacks.
     *
     * @var array<int, \Closure(\Illuminate\Http\Request): bool>
     */
    protected static $skipCallbacks = [];

    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @param  \Fruitcake\Cors\CorsService  $cors
     */
    public function __construct(Container $container, CorsService $cors)
    {
        $this->container = $container;
        $this->cors = $cors;
    }

    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Illuminate\Http\Response
     */
    public function handle($request, Closure $next)
    {
        foreach (static::$skipCallbacks as $callback) {
            if ($callback($request)) {
                return $next($request);
            }
        }

        $routeOptions = $this->resolveRouteCorsOptions($request);

        if ($routeOptions !== null) {
            $this->cors->setOptions($this->normalizeCorsOptions($routeOptions));

            if ($this->cors->isPreflightRequest($request)) {
                $response = $this->cors->handlePreflightRequest($request);

                $this->cors->varyHeader($response, 'Access-Control-Request-Method');

                return $response;
            }

            $response = $next($request);

            if ($request->getMethod() === 'OPTIONS') {
                $this->cors->varyHeader($response, 'Access-Control-Request-Method');
            }

            return $this->cors->addActualRequestHeaders($response, $request);
        }

        if (! $this->hasMatchingPath($request)) {
            return $next($request);
        }

        $this->cors->setOptions($this->container['config']->get('cors', []));

        if ($this->cors->isPreflightRequest($request)) {
            $response = $this->cors->handlePreflightRequest($request);

            $this->cors->varyHeader($response, 'Access-Control-Request-Method');

            return $response;
        }

        $response = $next($request);

        if ($request->getMethod() === 'OPTIONS') {
            $this->cors->varyHeader($response, 'Access-Control-Request-Method');
        }

        return $this->cors->addActualRequestHeaders($response, $request);
    }

    /**
     * Resolve route-level CORS options from the matched or matchable route.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|null
     */
    protected function resolveRouteCorsOptions(Request $request): ?array
    {
        $route = $this->matchRouteForCors($request);

        return $route?->effectiveCorsOptions();
    }

    /**
     * Attempt to match a route for the purpose of CORS resolution.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Routing\Route|null
     */
    protected function matchRouteForCors(Request $request): ?Route
    {
        $router = $this->container['router'];

        if ($request->getMethod() !== 'OPTIONS') {
            try {
                return $router->getRoutes()->match($request);
            } catch (\Throwable) {
                return null;
            }
        }

        $intendedMethod = $request->headers->get('Access-Control-Request-Method');

        if (! $intendedMethod) {
            return null;
        }

        $derivedRequest = Request::create(
            $request->getUri(),
            $intendedMethod,
            server: $request->server->all(),
        );

        try {
            return $router->getRoutes()->match($derivedRequest);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Normalize short-form CORS options into the shape expected by CorsService.
     *
     * @param  array  $options
     * @return array
     */
    protected function normalizeCorsOptions(array $options): array
    {
        return [
            'allowed_origins' => $options['origins'] ?? ['*'],
            'allowed_methods' => $options['methods'] ?? ['*'],
            'allowed_headers' => $options['headers'] ?? ['*'],
            'exposed_headers' => $options['exposed_headers'] ?? [],
            'max_age' => $options['max_age'] ?? 0,
            'supports_credentials' => $options['credentials'] ?? false,
            'allowed_origins_patterns' => [],
        ];
    }

    /**
     * Get the path from the configuration to determine if the CORS service should run.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function hasMatchingPath(Request $request): bool
    {
        $paths = $this->getPathsByHost($request->getHost());

        foreach ($paths as $path) {
            if ($path !== '/') {
                $path = trim($path, '/');
            }

            if ($request->fullUrlIs($path) || $request->is($path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the CORS paths for the given host.
     *
     * @param  string  $host
     * @return array
     */
    protected function getPathsByHost(string $host)
    {
        $paths = $this->container['config']->get('cors.paths', []);

        return $paths[$host] ?? array_filter($paths, function ($path) {
            return is_string($path);
        });
    }

    /**
     * Register a callback that instructs the middleware to be skipped.
     *
     * @param  \Closure  $callback
     * @return void
     */
    public static function skipWhen(Closure $callback)
    {
        static::$skipCallbacks[] = $callback;
    }

    /**
     * Flush the middleware's global state.
     *
     * @return void
     */
    public static function flushState()
    {
        static::$skipCallbacks = [];
    }
}
