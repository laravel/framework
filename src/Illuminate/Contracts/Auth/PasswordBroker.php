<?php

namespace Illuminate\Contracts\Auth;

use Closure;
use Illuminate\Auth\Enums\PasswordResetResult;

interface PasswordBroker
{
    /**
     * Constant representing a successfully sent reminder.
     *
     * @var \Illuminate\Auth\Enums\PasswordStatus
     */
    const RESET_LINK_SENT = PasswordResetResult::ResetLinkSent;

    /**
     * Constant representing a successfully reset password.
     *
     * @var \Illuminate\Auth\Enums\PasswordStatus
     */
    const PASSWORD_RESET = PasswordResetResult::PasswordReset;

    /**
     * Constant representing the user not found response.
     *
     * @var \Illuminate\Auth\Enums\PasswordStatus
     */
    const INVALID_USER = PasswordResetResult::InvalidUser;

    /**
     * Constant representing an invalid token.
     *
     * @var \Illuminate\Auth\Enums\PasswordStatus
     */
    const INVALID_TOKEN = PasswordResetResult::InvalidToken;

    /**
     * Constant representing a throttled reset attempt.
     *
     * @var \Illuminate\Auth\Enums\PasswordStatus
     */
    const RESET_THROTTLED = PasswordResetResult::Throttled;

    /**
     * Send a password reset link to a user.
     *
     * @param  array  $credentials
     * @param  \Closure|null  $callback
     * @return \Illuminate\Auth\Enums\PasswordStatus
     */
    public function sendResetLink(array $credentials, ?Closure $callback = null);

    /**
     * Reset the password for the given token.
     *
     * @param  array  $credentials
     * @param  \Closure  $callback
     * @return mixed
     */
    public function reset(array $credentials, Closure $callback);
}
