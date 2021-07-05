<?php

namespace Illuminate\Foundation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Http\Exceptions\DuplicatedIdempotencyKeyException;
use Illuminate\Foundation\Http\Exceptions\MissingIdempotencyKeyException;

class Idempotent
{
    const PREFIX = "IDEMPOTENT_";

    /**
     * The application instance.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * The application cache repository.
     *
     * @var \Illuminate\Cache\Repository
     */
    protected $cache;

    public function __construct(Application $app, CacheRepository $cache)
    {
        $this->app = $app;
        $this->cache = $cache;
    }

    /**
     * Determine if the HTTP request method is POST or PATCH & PUT.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function shouldSkip($request)
    {
        return !in_array($request->method(), ['POST', 'PATCH', 'PUT']);
    }

    public function handle(Request $request, Closure $next, $force = true)
    {
        if ($this->shouldSkip($request)) {
            return $next($request);
        }

        $idempotencyKey = $request->input('_idempotency_key') ?: $request->header('Idempotency-Key');
        $force = filter_var($force, FILTER_VALIDATE_BOOLEAN);

        if (!$idempotencyKey && $force === true) {
            throw new MissingIdempotencyKeyException();
        }

        $cacheKey = self::PREFIX.$idempotencyKey.$request->fingerprint();

        $cachedResponse = $this->cache->get($cacheKey);

        if ($cachedResponse) {
            throw new DuplicatedIdempotencyKeyException();
        }

        $response = $next($request);

        $statusCode = $response->getStatusCode();

        if ($statusCode >= 200 && $statusCode < 300) {
            $this->cache->set(
                $cacheKey,
                1,
                86400
            );
        }

        return $response;
    }
}
