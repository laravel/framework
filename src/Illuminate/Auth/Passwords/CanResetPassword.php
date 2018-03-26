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
     * @param  int  $expiration
     * @return void
     */
    public function sendPasswordResetNotification($expiration = 60)
    {
        $this->notify(new ResetPasswordNotification($expiration));
    }
}
