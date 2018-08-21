<?php

namespace Illuminate\Auth\Events;

class Attempting
{
    /**
     * The authentication guard implementation.
     *
     * @var \Illuminate\Contracts\Auth\StatefulGuard
     */
    public $guard;

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
     * @param  \Illuminate\Contracts\Auth\StatefulGuard  $guard
     * @param  array  $credentials
     * @param  bool  $remember
     * @return void
     */
    public function __construct($guard, $credentials, $remember)
    {
        $this->guard = $guard;
        $this->remember = $remember;
        $this->credentials = $credentials;
    }
}
