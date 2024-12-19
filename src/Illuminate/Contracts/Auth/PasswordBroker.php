<?php

namespace Illuminate\Contracts\Auth;

use Closure;
use Illuminate\Auth\Enums\PasswordStatus;

interface PasswordBroker
{
    /**
     * Constant representing a successfully sent reminder.
     *
     * @var \Illuminate\Auth\Enums\PasswordStatus
     * 
     * @deprecated Use \Illuminate\Auth\Enums\PasswordStatus::ResetLinkSent instead
     */
    const RESET_LINK_SENT = PasswordStatus::ResetLinkSent;

    /**
     * Constant representing a successfully reset password.
     *
     * @var \Illuminate\Auth\Enums\PasswordStatus
     * 
     * @deprecated Use \Illuminate\Auth\Enums\PasswordStatus::PasswordReset instead
     */
    const PASSWORD_RESET = PasswordStatus::PasswordReset;

    /**
     * Constant representing the user not found response.
     *
     * @var \Illuminate\Auth\Enums\PasswordStatus
     * 
     * @deprecated Use \Illuminate\Auth\Enums\PasswordStatus::InvalidUser instead
     */
    const INVALID_USER = PasswordStatus::InvalidUser;

    /**
     * Constant representing an invalid token.
     *
     * @var \Illuminate\Auth\Enums\PasswordStatus
     * 
     * @deprecated Use \Illuminate\Auth\Enums\PasswordStatus::InvalidToken instead
     */
    const INVALID_TOKEN = PasswordStatus::InvalidToken;

    /**
     * Constant representing a throttled reset attempt.
     *
     * @var \Illuminate\Auth\Enums\PasswordStatus
     * 
     * @deprecated Use \Illuminate\Auth\Enums\PasswordStatus::ResetThrottled instead
     */
    const RESET_THROTTLED = PasswordStatus::ResetThrottled;

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
