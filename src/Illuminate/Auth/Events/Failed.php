<?php

namespace Illuminate\Auth\Events;

class Failed
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
     * The authenticated user.
     *
     * @var \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public $user;

    /**
     * Create a new event instance.
     *
     * @param  array  $credentials
     * @param  bool  $remember
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $user
     */
    public function __construct($credentials, $remember, $user = null)
    {
        $this->credentials = $credentials;
        $this->remember = $remember;
        $this->user = $user;
    }
}
