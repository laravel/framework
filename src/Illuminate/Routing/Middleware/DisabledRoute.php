<?php

namespace Illuminate\Routing\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DisabledRoute
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route();

        if ($route && ($message = $route->getAction('disabled')) !== null) {
            $response = is_callable($message) ? $message($request) : null;

            if ($response !== null) {
                return $response;
            }

            $message = is_string($message) && ! empty($message)
                ? $message
                : 'This route is temporarily disabled.';

            return response($message, 503);
        }

        return $next($request);
    }
}
