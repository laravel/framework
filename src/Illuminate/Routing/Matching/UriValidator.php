<?php

namespace Illuminate\Routing\Matching;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;

class UriValidator implements ValidatorInterface
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
        if (($router = $route->getRouter()) && $router->getCurrentRequest()) {
            $path = $router->path();
        }
        else {
            $path = rawurldecode($request->path() == '/' ? '/' : '/'.$request->path());
        }

        return preg_match($route->getCompiled()->getRegex(), $path);
    }
}
