<?php

namespace Illuminate\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;

class HandleRouteCors extends HandleCors
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Illuminate\Http\Response
     */
    public function handle($request, Closure $next)
    {
        if ($this->shouldSkip($request)) {
            return $next($request);
        }

        $routeOptions = $this->resolveRouteCorsOptions($request);

        if ($routeOptions === null) {
            return $next($request);
        }

        $request->attributes->set(static::ROUTE_CORS_HANDLED_ATTRIBUTE, true);

        return $this->handleRequest($request, $next, $this->normalizeCorsOptions($routeOptions));
    }

    /**
     * Resolve the CORS options for the current route.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|null
     */
    protected function resolveRouteCorsOptions(Request $request): ?array
    {
        $route = $request->route();

        if (! $route instanceof Route) {
            return null;
        }

        if (! $request->isMethod('OPTIONS')) {
            return $route->effectiveCorsOptions();
        }

        $intendedMethod = strtoupper((string) $request->headers->get('Access-Control-Request-Method'));

        if ($intendedMethod !== '') {
            $alternateRoute = $route->getAction('cors_routes.'.$intendedMethod);

            if ($alternateRoute instanceof Route) {
                return $alternateRoute->effectiveCorsOptions();
            }
        }

        return $route->effectiveCorsOptions();
    }
}
