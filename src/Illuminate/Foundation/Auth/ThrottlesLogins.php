<?php

namespace Illuminate\Foundation\Auth;

use Illuminate\Http\Request;
use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Facades\Lang;

trait ThrottlesLogins
{
    /**
     * Determine if the user has too many failed login attempts.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function hasTooManyLoginAttempts(Request $request)
    {
        return app(RateLimiter::class)->tooManyAttempts(
            $request->input($this->loginUsername()).$request->ip(),
            $this->maxLoginAttempts(), $this->lockoutTime() / 60
        );
    }

    /**
     * Increment the login attempts for the user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return int
     */
    protected function incrementLoginAttempts(Request $request)
    {
        app(RateLimiter::class)->hit(
            $request->input($this->loginUsername()).$request->ip()
        );
    }

    /**
     * Redirect the user after determining they are locked out.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function sendLockoutResponse(Request $request)
    {
        $seconds = app(RateLimiter::class)->availableIn(
            $request->input($this->loginUsername()).$request->ip()
        );

        return redirect($this->loginPath())
            ->withInput($request->only($this->loginUsername(), 'remember'))
            ->withErrors([
                $this->loginUsername() => $this->getLockoutErrorMessage($seconds),
            ]);
    }

    /**
     * Get the login lockout error message.
     *
     * @param  int  $seconds
     * @return string
     */
    protected function getLockoutErrorMessage($seconds)
    {
        return Lang::has('auth.throttle')
            ? Lang::get('auth.throttle', ['seconds' => $seconds])
            : 'Too many login attempts. Please try again in '.$seconds.' seconds.';
    }

    /**
     * Clear the login locks for the given user credentials.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function clearLoginAttempts(Request $request)
    {
        app(RateLimiter::class)->clear(
            $request->input($this->loginUsername()).$request->ip()
        );
    }

    /**
     * Get the maximum number of login attempts for delaying further attempts.
     *
     * @return int
     */
    protected function maxLoginAttempts()
    {
        return property_exists($this, 'maxLoginAttempts') ? $this->maxLoginAttempts : 5;
    }

    /**
     * The number of seconds to delay further login attempts.
     *
     * @return int
     */
    protected function lockoutTime()
    {
        return property_exists($this, 'lockoutTime') ? $this->lockoutTime : 60;
    }
}
