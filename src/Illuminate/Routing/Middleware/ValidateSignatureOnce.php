<?php

namespace Illuminate\Routing\Middleware;

use Closure;
use Illuminate\Contracts\Cache\Factory as FactoryContract;
use Illuminate\Contracts\Config\Repository as ConfigContract;
use Illuminate\Routing\Exceptions\InvalidSignatureException;

use function date_create_from_format;

class ValidateSignatureOnce
{
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
    public function handle($request, Closure $next, $relative = null, $prefix = 'signed.once')
    {
        if ($this->hasValidSignature($request, $relative !== 'relative') &&
            $this->notHandledPreviously($request, $prefix)) {
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
     * @param  string  $prefix
     * @return bool
     */
    protected function notHandledPreviously($request, $prefix)
    {
        $key = $prefix.':'.$request->fingerprint();

        $store = cache()->store(config('cache.signed'));

        if ($store->has($key)) {
            return false;
        }

        $store->put($key, true, date_create_from_format('U', $request->query('expires')));

        return true;
    }
}
