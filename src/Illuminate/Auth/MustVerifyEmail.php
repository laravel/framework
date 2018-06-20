<?php

namespace Illuminate\Auth;

trait MustVerifyEmail
{
    /**
     * Determine if the user has verified their email address.
     *
     * @return bool
     */
    public function hasVerifiedEmail()
    {
        return (bool) $this->email_verified;
    }

    /**
     * Mark the given user's email as verified.
     *
     * @return void
     */
    public function markEmailAsVerified()
    {
        $this->forceFill(['email_verified' => true])->save();
    }

    /**
     * Send the email verification notification.
     *
     * @return void
     */
    public function sendEmailVerificationNotification()
    {
        $this->notify(new Notifications\VerifyEmail);
    }
}
