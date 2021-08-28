<?php

namespace Illuminate\Routing\Middleware;

use Closure;
use Illuminate\Routing\Exceptions\InvalidSignatureException;

use function cache;
use function config;
use function date_create_from_format;

class ValidateSignature
{
    /**
     * Prefix to use for the cache key.
     *
     * @var string
     */
    public static $prefix = 'signed.once';

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $relative
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Routing\Exceptions\InvalidSignatureException
     */
    public function handle($request, Closure $next, $relative = null, $once = null)
    {
        if ($request->hasValidSignature($relative !== 'relative') && ($once !== 'once' || $this->once($request))) {
            return $next($request);
        }

        throw new InvalidSignatureException();
    }

    /**
     * Check if the request was already handled.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function once($request)
    {
        if (! $request->query('expires')) {
            return false;
        }

        $key = $this->generateKey($request);
        $store = cache()->store(config('cache.signed'));

        if ($store->has($key)) {
            return false;
        }

        $store->put($key, true, date_create_from_format('U', $request->query('expires')));

        return true;
    }

    /**
     * Generates a key to use with the cache.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function generateKey($request)
    {
        return static::$prefix.':'. $request->query('signature');
    }

}
