<?php

namespace Illuminate\Auth\Authenticators;

use Closure;
class ExampleAuthenticator {
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($user, $credentials, Closure $next)
    {
        if(isset($user)&&isset($credentials))
        {
            return $next($user, $credentials);
        }else{
            return false;
        }

    }
}