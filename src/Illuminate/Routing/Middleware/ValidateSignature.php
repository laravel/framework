<?php

namespace Illuminate\Routing\Middleware;

use Closure;
use Illuminate\Support\Facades\URL;
use Illuminate\Routing\Exceptions\InvalidSignatureException;

class ValidateSignature
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Routing\Exceptions\InvalidSignatureException
     */
    public function handle($request, Closure $next)
    {
        if (URL::hasValidSignature($request)) {
            return $next($request);
        }

        if (URL::isExpired($request)) {
            throw InvalidSignatureException::forExpiredLink();
        }

        throw InvalidSignatureException::forInvalidSignature();
    }
}
