<?php

namespace Illuminate\Foundation\Http\Middleware;

use Closure;

class SetHeaders
{
    /**
     * All headers to set.
     *
     * @var array
     */
    protected $headers = [];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        /** @var \Illuminate\Http\Response $response */
        $response = $next($request);

        foreach ($this->headers as $name => $value) {
            $response->headers->set($name, $value);
        }

        return $response;
    }
}
