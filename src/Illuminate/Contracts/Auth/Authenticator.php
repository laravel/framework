<?php

namespace Illuminate\Contracts\Auth;


interface Authenticator {

    /**
     * Handle an incoming request.
     *
     * @param  array $input
     * @param  callable|Closure $next
     * @return bool|\Illuminate\Foundation\RedirectResponse
     */
    public function handle(array $input, Closure $next);
}