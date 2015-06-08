<?php

namespace Illuminate\Contracts\Routing;

use Closure;

/**
 * @deprecated since version 5.1.
 */
interface Middleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next);
}
