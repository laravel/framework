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
     * Create a new event instance.
     *
     * @param  array  $credentials
     * @param  bool  $remember
     * @return void
     */
    public function __construct($credentials, $remember)
    {
        $this->remember = $remember;
        $this->credentials = $credentials;
    }
}
