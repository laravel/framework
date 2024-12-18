<?php

namespace Illuminate\Auth\Enums;

enum PasswordStatus: string
{
    /**
     * Constant representing a successfully sent reminder.
     */
    case RESET_LINK_SENT = 'passwords.sent';

    /**
     * Constant representing a successfully reset password.
     */
    case PASSWORD_RESET = 'passwords.reset';

    /**
     * Constant representing the user not found response.
     */
    case INVALID_USER = 'passwords.user';

    /**
     * Constant representing an invalid token.
     */
    case INVALID_TOKEN = 'passwords.token';

    /**
     * Constant representing a throttled reset attempt.
     */
    case RESET_THROTTLED = 'passwords.throttled';
}
