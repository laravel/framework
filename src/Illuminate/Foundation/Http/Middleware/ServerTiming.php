<?php

namespace Illuminate\Foundation\Http\Middleware;

use Closure;

class ServerTiming
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        $duration = round(1000 * (microtime(true) - $request->server->get('REQUEST_TIME_FLOAT')), 2);
        $response->headers->set('Server-Timing', 'total;desc="Request execution time";dur=' . $duration, false);
        return $response;
    }
}
