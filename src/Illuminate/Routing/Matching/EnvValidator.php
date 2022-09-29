<?php

namespace Illuminate\Routing\Matching;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;

class EnvValidator
{
    /**
     * Validate a given rule against a route and request.
     *
     * @param \Illuminate\Routing\Route $route
     * @param \Illuminate\Http\Request $request
     * @return bool
     */
    public function matches(Route $route, Request $request)
    {
        return !(isset($route->action['env']) && !empty($route->action['env'])) ||
            in_array(strtolower(config('app.env')), array_map(function ($e) {
                return strtolower($e);
            }, $route->action['env']), true);
    }
}
