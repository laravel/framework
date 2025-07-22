<?php

namespace Illuminate\Http\Client\Middleware;

use Closure;
use Illuminate\Http\Client\HttpClientCurlBuilder;
use Psr\Http\Message\RequestInterface;

class HttpClientCurlGenerator
{
    /**
     * Handle an outgoing HTTP request.
     *
     * @param  bool  $pretty
     * @return Closure
     */
    public function handle(bool $pretty = false)
    {
        return function () use ($pretty): Closure {
            return function (RequestInterface $request) use ($pretty) {
               HttpClientCurlBuilder::forRequest($request)->pretty($pretty)->dd();
            };
        };
    }
}
