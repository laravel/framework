<?php

namespace Illuminate\Foundation\Http\Middleware;

use Closure;
use Illuminate\Container\Container;
use Illuminate\Foundation\Http\Exceptions\MalformedUrlException;
use Illuminate\Http\Request;
use Illuminate\Support\Defer\DeferredCallbackCollection;
use Symfony\Component\HttpFoundation\Response;

class ValidateUTF8Path
{
    /**
     * Validate that the incoming request has a valid UTF-8 encoded path.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next)
    {
        $decodedPath = rawurldecode($request->path());

        if (! mb_check_encoding($decodedPath, "UTF-8")) {
            throw new MalformedUrlException;
        }

        return $next($request);
    }
}
