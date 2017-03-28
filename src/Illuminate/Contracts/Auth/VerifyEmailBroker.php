<?php

namespace Illuminate\Contracts\Auth;

use Closure;
use Illuminate\Contracts\Auth\CanVerifyEmail as CanVerifyEmailContract;

interface VerifyEmailBroker
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
     * Send an email verification link to a user.
     *
     * @param  \Illuminate\Contracts\Auth\CanVerifyEmail  $user
     * @param  \Closure|null  $callback
     * @return string
     */
    public function sendVerificationLink(CanVerifyEmailContract $user, Closure $callback = null);

    /**
     * Verify the email address for the given token.
     *
     * @param  \Illuminate\Contracts\Auth\CanVerifyEmail  $user
     * @param  string  $token
     * @return mixed
     */
    public function verify(CanVerifyEmailContract $user, $token);
}
