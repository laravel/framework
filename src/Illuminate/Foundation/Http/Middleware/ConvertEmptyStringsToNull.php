<?php

namespace Illuminate\Foundation\Http\Middleware;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ConvertEmptyStringsToNull extends TransformsRequest
{
    /**
     * The attributes that should not be converted.
     *
     * @var array<int, string>
     */
    protected $except = [
        //
    ];

    /**
     * The globally ignored attributes that should not be converted.
     *
     * @var array
     */
    protected static $neverConvert = [];

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
     * @param  \Closure  $next
     * @return mixed
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
     * @param  mixed  $value
     * @return mixed
     */
    protected function transform($key, $value)
    {
        $except = array_merge($this->except, static::$neverConvert);

        if ($this->shouldSkip($key, $except)) {
            return $value;
        }

        return $value === '' ? null : $value;
    }

    /**
     * Determine if the given key should be skipped.
     *
     * @param  string  $key
     * @param  array  $except
     * @return bool
     */
    protected function shouldSkip($key, $except)
    {
        return Str::is($except, $key);
    }

    /**
     * Indicate that the given attributes should never be converted.
     *
     * @param  array|string  $attributes
     * @return void
     */
    public static function except($attributes)
    {
        static::$neverConvert = array_values(array_unique(
            array_merge(static::$neverConvert, Arr::wrap($attributes))
        ));
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
        static::$neverConvert = [];

        static::$skipCallbacks = [];
    }
}
