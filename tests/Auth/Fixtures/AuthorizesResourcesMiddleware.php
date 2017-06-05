<?php

namespace Illuminate\Tests\Auth\Fixtures;

use Closure;

class AuthorizesResourcesMiddleware
{
    public function handle($request, Closure $next, $method, $parameter)
    {
        return "caught can:{$method},{$parameter}";
    }
}
