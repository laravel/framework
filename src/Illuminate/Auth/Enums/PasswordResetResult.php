<?php

namespace Illuminate\Auth\Enums;

enum PasswordResetResult: string
{
    /**
     * Constant representing a successfully sent reminder.
     */
    case ResetLinkSent = 'passwords.sent';

    /**
     * Constant representing a successfully reset password.
     */
    case PasswordReset = 'passwords.reset';

    /**
     * Constant representing the user not found response.
     */
    case InvalidUser = 'passwords.user';

    /**
     * Constant representing an invalid token.
     */
    case InvalidToken = 'passwords.token';

    /**
     * Constant representing a throttled reset attempt.
     */
    case Throttled = 'passwords.throttled';
}
