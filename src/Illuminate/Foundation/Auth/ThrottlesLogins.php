<?php

namespace Illuminate\Foundation\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Cache;

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
        $attempts = $this->getLoginAttempts($request);

        $lockedOut = Cache::has($this->getLoginLockExpirationKey($request));

        if ($attempts > 5 || $lockedOut) {
            if (! $lockedOut) {
                Cache::put($this->getLoginLockExpirationKey($request), time() + 60, 1);
            }

            return true;
        }

        return false;
    }

    /**
     * Get the login attempts for the user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return int
     */
    protected function getLoginAttempts(Request $request)
    {
        return Cache::get($this->getLoginAttemptsKey($request)) ?: 0;
    }

    /**
     * Increment the login attempts for the user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return int
     */
    protected function incrementLoginAttempts(Request $request)
    {
        Cache::add($key = $this->getLoginAttemptsKey($request), 1, 1);

        return (int) Cache::increment($key);
    }

    /**
     * Redirect the user after determining they are locked out.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function sendLockoutResponse(Request $request)
    {
        $seconds = (int) Cache::get($this->getLoginLockExpirationKey($request)) - time();

        $message = Lang::has('auth.throttle')
                    ? Lang::get('auth.throttle', ['seconds' => $seconds])
                    : 'Too many login attempts. Please try again in '.$seconds.' seconds.';

        return redirect($this->loginPath())
            ->withInput($request->only($this->loginUsername(), 'remember'))
            ->withErrors([
                $this->loginUsername() => $message,
            ]);
    }

    /**
     * Clear the login locks for the given user credentials.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function clearLoginAttempts(Request $request)
    {
        Cache::forget($this->getLoginAttemptsKey($request));

        Cache::forget($this->getLoginLockExpirationKey($request));
    }

    /**
     * Get the login attempts cache key.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function getLoginAttemptsKey(Request $request)
    {
        $username = $request->input($this->loginUsername());

        return 'login:attempts:'.md5($username.$request->ip());
    }

    /**
     * Get the login lock cache key.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function getLoginLockExpirationKey(Request $request)
    {
        $username = $request->input($this->loginUsername());

        return 'login:expiration:'.md5($username.$request->ip());
    }
}
