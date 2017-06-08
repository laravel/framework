<?php

namespace Illuminate\Routing\Matching;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;

class SubdomainValidator implements ValidatorInterface
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
        if (is_null($route->subdomain())) {
            return true;
        }

        $subdomains = implode('\.|', $route->subdomain());
        $regex = "/^($subdomains).*/i";

        return preg_match($regex, $request->getHost());
    }
}
