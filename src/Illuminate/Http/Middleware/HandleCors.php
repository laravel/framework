<?php

namespace Illuminate\Http\Middleware;

use Closure;
use Fruitcake\Cors\CorsService;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class HandleCors
{
    public const ROUTE_CORS_HANDLED_ATTRIBUTE = '_laravel_route_cors_handled';

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
        if ($this->shouldSkip($request) || ! $this->hasMatchingPath($request)) {
            return $next($request);
        }

        if ($this->cors->isPreflightRequest($request)) {
            $this->cors->setOptions($this->resolveOptionsForPreflight($request));

            $response = $this->cors->handlePreflightRequest($request);

            $this->cors->varyHeader($response, 'Access-Control-Request-Method');

            return $response;
        }

        $response = $next($request);

        if ($request->attributes->get(static::ROUTE_CORS_HANDLED_ATTRIBUTE)) {
            return $response;
        }

        return $this->handleRequest($request, fn () => $response, $this->container['config']->get('cors', []));
    }

    /**
     * Resolve the CORS options to use when answering a preflight request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function resolveOptionsForPreflight(Request $request): array
    {
        $globalOptions = $this->container['config']->get('cors', []);

        $intendedMethod = strtoupper((string) $request->headers->get('Access-Control-Request-Method'));

        if ($intendedMethod === '') {
            return $globalOptions;
        }

        try {
            $probe = $request->duplicate();
            $probe->setMethod($intendedMethod);

            $route = $this->container['router']->getRoutes()->match($probe);
        } catch (NotFoundHttpException|MethodNotAllowedHttpException) {
            return $globalOptions;
        }

        if ($route instanceof Route && ($routeOptions = $route->effectiveCorsOptions()) !== null) {
            return $this->normalizeCorsOptions($routeOptions);
        }

        return $globalOptions;
    }

    /**
     * Determine whether the middleware should be skipped.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function shouldSkip(Request $request): bool
    {
        foreach (static::$skipCallbacks as $callback) {
            if ($callback($request)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Handle the request using the given CORS options.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  array  $options
     * @return \Illuminate\Http\Response
     */
    protected function handleRequest(Request $request, Closure $next, array $options)
    {
        $this->cors->setOptions($options);

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
