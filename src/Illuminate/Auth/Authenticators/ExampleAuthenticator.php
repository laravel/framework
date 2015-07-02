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
    public function handle($input, Closure $next) {
        $user = $input['user'];
        $credentials = $input['credentials'];

        if(isset($credentials['test']) && $credentials['test'] == 'abc'){
            return $next($user, $credentials);
        }

        return redirect('/test/auth');
    }
}