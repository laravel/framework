<?php

namespace Illuminate\Auth\Events;

class Failed
{
    /**
     * The user the attempter was trying to authenticate as.
     *
     * @var \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public $user;

    /**
     * The credentials provided by the attempter.
     *
     * @var array
     */
    public $credentials;

    /**
     * The guard the user failed to authenticated to.
     *
     * @var \Illuminate\Contracts\Auth\StatefulGuard
     */
    public $guard;

    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $user
     * @param  array  $credentials
     * @param  \Illuminate\Contracts\Auth\StatefulGuard  $guard
     * @return void
     */
    public function __construct($user, $credentials, $guard)
    {
        $this->user = $user;
        $this->credentials = $credentials;
        $this->guard = $guard;
    }
}
