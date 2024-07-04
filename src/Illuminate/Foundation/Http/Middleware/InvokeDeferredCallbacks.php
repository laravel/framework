<?php

namespace Illuminate\Foundation\Http\Middleware;

use Closure;
use Illuminate\Container\Container;
use Illuminate\Foundation\Defer\DeferredCallbackCollection;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InvokeDeferredCallbacks
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next)
    {
        return $next($request);
    }

    /**
     * Invoke the deferred callbacks.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @return void
     */
    public function terminate(Request $request, Response $response)
    {
        $deferred = Container::getInstance()->make(DeferredCallbackCollection::class);

        while ($callback = $deferred->shift()) {
            if ($response->isSuccessful() || $callback->always) {
                rescue($callback);
            }
        }
    }
}
