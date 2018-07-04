<?php

namespace Illuminate\Foundation\Http\Middleware;

use Closure;

class SecuresRequest
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ( ! $request->secure() && config('app.https')) {
            return redirect()->secure($request->path(), 301);
        }

        return $next($request);
    }
}
