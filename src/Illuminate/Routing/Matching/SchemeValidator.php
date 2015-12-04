<?php

namespace Illuminate\Routing\Matching;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;

class SchemeValidator implements ValidatorInterface
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
        if ($route->httpOnly()) {
            return ! $request->isSecure();
        } elseif ($route->secure()) {
            return $request->isSecure();
        }

        return true;
    }
}
