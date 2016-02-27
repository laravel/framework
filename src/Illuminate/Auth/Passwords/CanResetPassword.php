<?php

namespace Illuminate\Auth\Passwords;

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
     * Reset the user's password.
     */
    public function resetPassword($password)
    {
        $this->password = bcrypt($password);
    }
}
