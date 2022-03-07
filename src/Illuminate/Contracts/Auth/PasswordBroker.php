<?php

namespace Illuminate\Contracts\Auth;

use Closure;

interface PasswordBroker
{
    /**
     * Constant representing a successfully sent reminder.
     *
     * @var string
     *
     * @deprecated
     */
    const RESET_LINK_SENT = 'passwords.sent';

    /**
     * Constant representing a successfully reset password.
     *
     * @var string
     *
     * @deprecated
     */
    const PASSWORD_RESET = 'passwords.reset';

    /**
     * Constant representing the user not found response.
     *
     * @var string
     *
     * @deprecated
     */
    const INVALID_USER = 'passwords.user';

    /**
     * Constant representing an invalid token.
     *
     * @var string
     *
     * @deprecated
     */
    const INVALID_TOKEN = 'passwords.token';

    /**
     * Constant representing a throttled reset attempt.
     *
     * @var string
     *
     * @deprecated
     */
    const RESET_THROTTLED = 'passwords.throttled';

    /**
     * Send a password reset link to a user.
     *
     * @param  array  $credentials
     * @param  \Closure|null  $callback
     * @return \Illuminate\Contracts\Auth\PasswordResetResponse
     */
    public function sendResetLink(array $credentials, Closure $callback = null): PasswordResetResponse;

    /**
     * Reset the password for the given token.
     *
     * @param  array  $credentials
     * @param  \Closure  $callback
     * @return \Illuminate\Contracts\Auth\PasswordResetResponse
     */
    public function reset(array $credentials, Closure $callback): PasswordResetResponse;
}
