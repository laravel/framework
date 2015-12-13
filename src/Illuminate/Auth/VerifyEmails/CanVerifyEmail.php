<?php

namespace Illuminate\Auth\VerifyEmails;

use Illuminate\Mail\Message;
use Illuminate\Support\Facades\VerifyEmail;

trait CanVerifyEmail
{
    /**
     * Get the email address that we're verifying.
     *
     * @return string
     */
    public function getEmailToVerify()
    {
        return $this->email;
    }

    /**
     * Get the email verification boolean.
     *
     * @return string
     */
    public function getVerified()
    {
        return $this->{$this->getVerifiedName()};
    }

    /**
     * Set the email verification boolean.
     *
     * @param  bool  $value
     * @return void
     */
    public function setVerified($value)
    {
        $this->{$this->getVerifiedName()} = $value;
    }

    /**
     * Get the column name for the email verification boolean.
     *
     * @return string
     */
    public function getVerifiedName()
    {
        return 'verified';
    }

    /**
     * Get the email subject line to be used for the verification email.
     *
     * @return string
     */
    public function getVerifyEmailSubject()
    {
        return property_exists($this, 'subject') ? $this->subject : 'Your Email Verification Link';
    }

    /**
     * Unverify the user's email address.
     *
     * @return void
     */
    public function unverify()
    {
        VerifyEmail::sendVerificationLink($this, function (Message $message) {
            $message->subject($this->getVerifyEmailSubject());
        });

        $this->setVerified(false);
    }

    /**
     * Check if the email address is verified.
     *
     * @return bool
     */
    public function isVerified()
    {
        return (bool) $this->getVerified();
    }
}
