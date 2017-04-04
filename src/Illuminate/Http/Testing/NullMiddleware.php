<?php

namespace Illuminate\Http\Testing;

use Closure;

class NullMiddleware
{
    public function handle($request, Closure $next)
    {
        return $next($request);
    }
}
