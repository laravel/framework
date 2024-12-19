<?php

namespace Illuminate\Auth\Enums;

enum PasswordResetResult: string
{
    /**
     * Result indicating a successfully sent password reset email.
     */
    case ResetLinkSent = 'passwords.sent';

    /**
     * Result representing a successfully reset password.
     */
    case PasswordReset = 'passwords.reset';

    /**
     * Result indicating the user is invalid.
     */
    case InvalidUser = 'passwords.user';

    /**
     * Result indicating the token is invalid.
     */
    case InvalidToken = 'passwords.token';

    /**
     * Result indicating the password reset attempt has been throttled.
     */
    case Throttled = 'passwords.throttled';
}
