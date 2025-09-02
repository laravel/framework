<?php

namespace Illuminate\Foundation\Http\Middleware;

use Closure;

class ConvertEmptyStringsToNull extends TransformsRequest
{
    /**
     * All of the registered skip callbacks.
     *
     * @var array
     */
    protected static $skipCallbacks = [];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function handle($request, Closure $next)
    {
        foreach (static::$skipCallbacks as $callback) {
            if ($callback($request)) {
                return $next($request);
            }
        }

        return parent::handle($request, $next);
    }

    /**
     * Transform the given value.
     *
     * @param  string  $key
     */
    protected function transform($key, $value)
    {
        return $value === '' ? null : $value;
    }

    /**
     * Register a callback that instructs the middleware to be skipped.
     *
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
