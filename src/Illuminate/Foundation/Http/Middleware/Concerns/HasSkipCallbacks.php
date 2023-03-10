<?php

namespace Illuminate\Foundation\Http\Middleware\Concerns;

use Closure;

trait HasSkipCallbacks
{
    /**
     * All of the registered skip callbacks.
     *
     * @var array
     */
    protected static $skipCallbacks = [];

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    public function shouldSkipDueToCallback($request)
    {
        foreach (static::$skipCallbacks as $callback) {
            if ($callback($request)) {
                return true;
            }
        }

        return false;
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
     * Clears all currently registered callbacks
     *
     * @return void
     */
    public static function clearSkips()
    {
        static::$skipCallbacks = [];
    }
}
