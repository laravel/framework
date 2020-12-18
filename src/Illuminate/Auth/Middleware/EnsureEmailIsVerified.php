<?php

namespace Illuminate\Auth\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;

class EnsureEmailIsVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $redirectToRoute
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse|null
     */
    public function handle($request, Closure $next, $redirectToRoute = null)
    {
        if ($request->user() instanceof MustVerifyEmail)
        {
            if ($request->user()->hasVerifiedEmail() && Route::is(($redirectToRoute ?: 'verification.notice')))
                return $request->expectsJson()
                    ? abort(403, 'Your email address has been verified.')
                    : Redirect::intended(RouteServiceProvider::HOME);

            if (!$request->user()->hasVerifiedEmail() && !Route::is(($redirectToRoute ?: 'verification.notice')))
                return $request->expectsJson()
                    ? abort(403, 'Your email address is not verified.')
                    : Redirect::guest(URL::route($redirectToRoute ?: 'verification.notice'));
        }

        return $next($request);
    }
}
