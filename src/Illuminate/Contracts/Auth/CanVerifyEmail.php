<?php

namespace Illuminate\Contracts\Auth;

interface CanVerifyEmail
{
    /**
     * Get the email address that we're verifying.
     *
     * @return string
     */
    public function getEmailToVerify();

    /**
     * Get the email verification boolean.
     *
     * @return string
     */
    public function getVerified();

    /**
     * Set the email verification boolean.
     *
     * @param  string  $value
     * @return void
     */
    public function setVerified($value);

    /**
     * Get the column name for the email verification boolean.
     *
     * @return string
     */
    public function getVerifiedName();

    /**
     * Unverify the user's email address.
     *
     * @return void
     */
    public function unverify();

    /**
     * Check if the email address is verified.
     *
     * @return bool
     */
    public function isVerified();
}
