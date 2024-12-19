<?php

namespace Illuminate\Auth\Passwords;

class PasswordResetResult
{
    /**
     * Result indicating a successfully sent password reset email.
     */
    const ResetLinkSent = 'passwords.sent';

    /**
     * Result representing a successfully reset password.
     */
    const PasswordReset = 'passwords.reset';

    /**
     * Result indicating the user is invalid.
     */
    const InvalidUser = 'passwords.user';

    /**
     * Result indicating the token is invalid.
     */
    const InvalidToken = 'passwords.token';

    /**
     * Result indicating the password reset attempt has been throttled.
     */
    const Throttled = 'passwords.throttled';
}
