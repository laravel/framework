<?php

namespace Illuminate\Routing\Middleware;

use Closure;
use Illuminate\Http\Request;

class HandleAtomicLocks
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function handle(Request $request, Closure $next)
    {
        $route = $request->route();

        $attribute = $route->getLockedAttribute();

        if (! $attribute) {
            return $next($request);
        }

        $lockKey = $this->resolveLockKey($request, $attribute);

        $lock = app('cache')->lock($lockKey, $attribute->seconds);

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
     * Resolve the lock key for the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Routing\Attributes\Controllers\Locked  $attribute
     * @return string
     */
    protected function resolveLockKey($request, $attribute)
    {
        $baseKey = 'laravel_lock:'.$request->route()->getName();
        $userKey = $request->user()?->id ?: $request->ip();

        return $attribute->key
            ? "{$baseKey}:{$attribute->key}:{$userKey}"
            : "{$baseKey}:{$userKey}";
    }
}

