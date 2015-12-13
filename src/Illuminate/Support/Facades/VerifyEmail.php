<?php

namespace Illuminate\Support\Facades;

/**
 * @see \Illuminate\Auth\Emails\VerifyEmailBroker
 */
class VerifyEmail extends Facade
{
    /**
     * Constant representing a successfully sent verification link.
     *
     * @var string
     */
    const VERIFY_LINK_SENT = 'verify_email.sent';

    /**
     * Constant representing a successfully verified email address.
     *
     * @var string
     */
    const EMAIL_VERIFIED = 'verify_email.verified';

    /**
     * Constant representing an invalid token.
     *
     * @var string
     */
    const INVALID_TOKEN = 'verify_email.token';

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'auth.verify_email';
    }
}
