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

        if ($route && ($message = $route->getDisabled()) !== null) {
            // If it's a callback, execute it
            if (is_callable($message)) {
                $response = $message($request);

                // If callback returns null or false, allow the route to proceed
                if ($response === null || $response === false) {
                    return $next($request);
                }

                return $response;
            }

            // For boolean false, allow the route to proceed
            if ($message === false) {
                return $next($request);
            }

            // For boolean true or non-empty string, disable the route
            $message = is_string($message) && ! empty($message)
                ? $message
                : 'This route is temporarily disabled.';

            return response($message, 503);
        }

        return $next($request);
    }
}
