<?php

namespace Illuminate\Routing\Middleware;

use Closure;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Http\Request;

class HandleAtomicLocks
{
    /**
     * The instance of the cache factory.
     *
     * @var \Illuminate\Contracts\Cache\Factory
     */
    protected $cache;

    /**
     * Create the Middleware instance.
     *
     * @param  \Illuminate\Contracts\Cache\Factory  $cache
     * @return void
     */
    public function __construct(CacheFactory $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Handle the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $route = $request->route();

        if (! $attribute = $route->getLockedAttribute()) {
            return $next($request);
        }

        $lockKey = $this->resolveLockKey($request, $attribute);

        $lock = $this->cache->lock($lockKey, $attribute->seconds);

        if (! $lock->get()) {
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(423, 'Locked.');
        }

        try {
            return $next($request);
        } finally {
            $lock->release();
        }
    }

    /**
     * Create the lock key corresponding to the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Routing\Attributes\Locked  $attribute
     * @return string
     */
    protected function resolveLockKey($request, $attribute)
    {
        $routeIdentifier = $request->route()->getName() ?? $request->route()->getActionName();

        $baseKey = 'laravel_lock:'.md5($routeIdentifier);

        $userKey = $request->user()?->getAuthIdentifier() ?: $request->ip();

        return $attribute->key
            ? "{$baseKey}:{$attribute->key}:{$userKey}"
            : "{$baseKey}:{$userKey}";
    }
}
