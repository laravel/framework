<?php

namespace Illuminate\Contracts\Auth\Identity;

interface HasPassword
{
    /**
     * Get the name of the password attribute for the user.
     *
     * @return string
     */
    public function getAuthPasswordName();

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPassword();
}
