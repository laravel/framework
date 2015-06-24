<?php

namespace Illuminate\Foundation\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

trait AuthenticatesUsers
{
    use RedirectsUsers;

    /**
     * Show the application login form.
     *
     * @return \Illuminate\Http\Response
     */
    public function getLogin()
    {
        return view('auth.login');
    }

    /**
     * Handle a login request to the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function postLogin(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email', 'password' => 'required',
        ]);

        if ($this->tooManyLoginAttempts($request)) {
            return $this->sendLockoutResponse($request);
        }

        if (Auth::attempt($this->getCredentials($request), $request->has('remember'))) {
            $this->clearLoginAttempts($request);

            return redirect()->intended($this->redirectPath());
        }

        $this->incrementLoginAttempts($request);

        return redirect($this->loginPath())
            ->withInput($request->only('email', 'remember'))
            ->withErrors([
                'email' => $this->getFailedLoginMessage(),
            ]);
    }

    /**
     * Determine if the user is locked out.
     *
     * @param  Request  $request
     * @return bool
     */
    protected function tooManyLoginAttempts(Request $request)
    {
        $attempts = $this->getLoginAttempts($request);

        if ($attempts > 3) {
            $expiration = $this->calculateLockExpiration($attempts);

            if (Cache::add($timeKey = $this->getLoginLockExpirationKey($request), $expiration, \Carbon\Carbon::createFromTimestamp($expiration))) {
                $this->incrementLoginAttempts($request);
            }

            return (bool) Cache::get($timeKey);
        }

        return false;
    }

    /**
     * Get the login attempts for the user.
     *
     * @param  Request  $request
     * @return int
     */
    protected function getLoginAttempts(Request $request)
    {
        return Cache::get($this->getLoginAttemptsKey($request)) ?: 0;
    }

    /**
     * Increment the login attempts for the user.
     *
     * @param  Request  $request
     * @return int
     */
    protected function incrementLoginAttempts(Request $request)
    {
        Cache::add($key = $this->getLoginAttemptsKey($request), 1, 15);

        return (int) Cache::increment($key);
    }

    /**
     * Calculate the next lock expiration time based on the login attempts.
     *
     * @param  int  $attempts
     * @return int
     */
    protected function calculateLockExpiration($attempts)
    {
        return min(time() + 120, time() + (($attempts - 3) * 15));
    }

    /**
     * Redirect the user after determining they are locked out.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function sendLockoutResponse(Request $request)
    {
        $seconds = (int) Cache::get($this->getLoginLockExpirationKey($request)) - time();

        return redirect($this->loginPath())
            ->withInput($request->only('email', 'remember'))
            ->withErrors([
                'email' => 'Too many login attempts. Please try again in '.$seconds.' seconds.',
            ]);
    }

    /**
     * Clear the login locks for the given user credentials.
     *
     * @param  Request  $request
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
     * @param  Request  $request
     * @return string
     */
    protected function getLoginAttemptsKey(Request $request)
    {
        return 'login:attempts:'.md5($request->email.$request->ip());
    }

    /**
     * Get the login lock cache key.
     *
     * @param  Request  $request
     * @return string
     */
    protected function getLoginLockExpirationKey(Request $request)
    {
        return 'login:expiration:'.md5($request->email.$request->ip());
    }

    /**
     * Get the needed authorization credentials from the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function getCredentials(Request $request)
    {
        return $request->only('email', 'password');
    }

    /**
     * Get the failed login message.
     *
     * @return string
     */
    protected function getFailedLoginMessage()
    {
        return 'These credentials do not match our records.';
    }

    /**
     * Log the user out of the application.
     *
     * @return \Illuminate\Http\Response
     */
    public function getLogout()
    {
        Auth::logout();

        return redirect(property_exists($this, 'redirectAfterLogout') ? $this->redirectAfterLogout : '/');
    }

    /**
     * Get the path to the login route.
     *
     * @return string
     */
    public function loginPath()
    {
        return property_exists($this, 'loginPath') ? $this->loginPath : '/auth/login';
    }
}
