<?php

namespace Illuminate\Auth;

use Illuminate\Auth\Notifications\VerifyEmail;

trait MustVerifyEmail
{
    /**
     * The column name of the "email verified at" property.
     *
     * @var string
     */
    protected $emailVerifiedAtName = 'email_verified_at';

    /**
     * Determine if the user has verified their email address.
     *
     * @return bool
     */
    public function hasVerifiedEmail()
    {
        return ! is_null($this->{$this->getEmailVerifiedAtName()});
    }

    /**
     * Mark the given user's email as verified.
     *
     * @return bool
     */
    public function markEmailAsVerified()
    {
        return $this->forceFill([
            $this->getEmailVerifiedAtName() => $this->freshTimestamp(),
        ])->save();
    }

    /**
     * Send the email verification notification.
     *
     * @return void
     */
    public function sendEmailVerificationNotification()
    {
        $this->notify(new VerifyEmail);
    }

    /**
     * Get the email address that should be used for verification.
     *
     * @return string
     */
    public function getEmailForVerification()
    {
        return $this->email;
    }

    /**
     * Get the column name for the "email verified at" property
     *
     * @return string
     */
    public function getEmailVerifiedAtName()
    {
        return $this->emailVerifiedAtName;
    }
}
