<?php

namespace Illuminate\Contracts\Auth;

enum PasswordResetResponse: string
{
    /**
     * Case representing a successfully sent reminder.
     */
    case ResetLinkSent = 'passwords.sent';

    /**
     * Case representing a successfully reset password.
     */
    case PasswordReset = 'passwords.reset';

    /**
     * Case representing the user not found response.
     */
    case InvalidUser = 'passwords.user';

    /**
     * Case representing an invalid token.
     */
    case InvalidToken = 'passwords.token';

    /**
     * Case representing a throttled reset attempt.
     */
    case ResetThrottled = 'passwords.throttled';
}
