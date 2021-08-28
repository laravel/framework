<?php

namespace Illuminate\Routing\Middleware;

use Closure;
use Illuminate\Contracts\Cache\Factory as FactoryContract;
use Illuminate\Routing\Exceptions\InvalidSignatureException;

use function date_create_from_format;

class ValidateSignatureOnce
{
    /**
     * The cache factory.
     *
     * @var \Illuminate\Contracts\Cache\Factory
     */
    protected $cache;

    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Contracts\Cache\Factory  $cache
     */
    public function __construct(FactoryContract $cache)
    {
        $this->cache = $cache;
    }

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
    public function handle($request, Closure $next, $relative = null, $store = null, $prefix = 'signed_once')
    {
        if ($this->hasValidSignature($request, $relative !== 'relative') &&
            $this->notHandledPreviously($request, $store, $prefix)) {
            return $next($request);
        }

        throw new InvalidSignatureException;
    }

    /**
     * Check if the request has an expiring valid signature.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  bool  $relative
     * @return bool
     */
    protected function hasValidSignature($request, $relative)
    {
        return $request->query('expires') && $request->hasValidSignature($relative);
    }

    /**
     * Checks if the current request was not already handled by the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string|null  $store
     * @param  string  $prefix
     * @return bool
     */
    protected function notHandledPreviously($request, $store, $prefix)
    {
        $key = $prefix.':'.$request->fingerprint();

        if ($this->cache->store($store)->has($key)) {
            return false;
        }

        $this->cache->store($store)->put($key, true, date_create_from_format('U', $request->query('expires')));

        return true;
    }
}
