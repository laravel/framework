<?php

namespace Illuminate\Auth\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Factory as Auth;
use InvalidArgumentException;

/**
 * Optional authentication: never aborts the request; sets the active guard when credentials match.
 *
 * On the same route as {@see Authenticate}, list this middleware (e.g. optionalAuth) with the same
 * guards or omit parameters to inherit guards from the required Authenticate middleware; the stack
 * will run only optional authentication for those guards.
 */
class OptionalAuthenticate
{
    /**
     * The authentication factory instance.
     *
     * @var \Illuminate\Contracts\Auth\Factory
     */
    protected $auth;

    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Contracts\Auth\Factory  $auth
     */
    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Specify the guards for the middleware.
     *
     * @param  string  $guard
     * @param  string  ...$others
     * @return string
     */
    public static function using($guard, ...$others)
    {
        return static::class.':'.implode(',', [$guard, ...$others]);
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  ...$guards
     * @return mixed
     */
    public function handle($request, Closure $next, ...$guards)
    {
        $this->authenticate($request, $guards);

        return $next($request);
    }

    /**
     * Try to authenticate using the given guards without throwing.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  array  $guards
     * @return void
     */
    protected function authenticate($request, array $guards)
    {
        if (empty($guards)) {
            $guards = [null];
        }

        if (! $this->guardsContainExplicitDriver($guards)) {
            throw new InvalidArgumentException(
                'The [optionalAuth] middleware requires at least one guard (e.g. [optionalAuth:sanctum]), unless it is used on the same route as [auth] middleware so guards can be inherited.'
            );
        }

        foreach ($guards as $guard) {
            if ($this->auth->guard($guard)->check()) {
                return $this->auth->shouldUse($guard);
            }
        }

        $this->preferGuardsForUserResolution($guards);
    }

    /**
     * @param  array<int, string|null>  $guards
     */
    protected function guardsContainExplicitDriver(array $guards): bool
    {
        foreach ($guards as $guard) {
            if ($guard !== null) {
                return true;
            }
        }

        return false;
    }

    /**
     * Prefer the attempted named guards for Auth / request user resolution when unauthenticated.
     *
     * @param  array<int, string|null>  $guards
     * @return void
     */
    protected function preferGuardsForUserResolution(array $guards): void
    {
        foreach ($guards as $guard) {
            if ($guard !== null) {
                $this->auth->shouldUse($guard);

                return;
            }
        }
    }
}
