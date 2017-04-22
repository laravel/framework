<?php

namespace Illuminate\Auth\Passwords;

use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;

trait CanResetPassword
{
    /**
     * Get the e-mail address where password reset links are sent.
     *
     * @return string
     */
    public function getEmailForPasswordReset()
    {
        return $this->email;
    }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @param  int  $expiration
     * @return void
     */
    public function sendPasswordResetNotification($token, $expiration)
    {
        $this->notify(new ResetPasswordNotification($token, $expiration));
    }
}
