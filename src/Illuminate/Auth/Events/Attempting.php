<?php

namespace Illuminate\Auth\Events;

class Attempting
{
    /**
     * The credentials for the user.
     *
     * @var array
     */
    public $credentials;

    /**
     * Indicates if the user should be "remembered".
     *
     * @var bool
     */
    public $remember;

    /**
     * Indicates if the user should be authenticated if successful.
     *
     * @var bool
     */
    public $login;

    /**
     * Create a new event instance.
     *
     * @param  array  $credentials
     * @param  bool  $remember
     * @param  bool  $login
     */
    public function __construct($credentials, $remember, $login)
    {
        $this->login = $login;
        $this->remember = $remember;
        $this->credentials = $credentials;
    }
}
