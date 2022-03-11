<?php

namespace Illuminate\Routing\Matching;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;

class CallbackValidator implements ValidatorInterface
{
    /**
     * Validate a given rule against a route and request.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    public function matches(Route $route, Request $request)
    {
        $path = rtrim($request->getPathInfo(), '/') ?: '/';

        if (! preg_match($route->getCompiled()->getRegex(), rawurldecode($path), $matches)) {
            return false;
        }

        foreach ($route->callbacks as $param => $callback) {
            if (! $callback($matches[$param] ?? null)) {
                return false;
            }
        }

        return true;
    }
}
