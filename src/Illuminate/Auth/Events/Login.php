<?php

namespace Illuminate\Auth\Events;

use Illuminate\Queue\SerializesModels;

class Login
{
    use SerializesModels;

    /**
     * The authenticated user.
     *
     * @var \Illuminate\Contracts\Auth\Authenticatable
     */
    public $user;

    /**
     * Indicates if the user should be "remembered".
     *
     * @var bool
     */
    public $remember;

    /**
     * The guard the user authenticated to.
     *
     * @var \Illuminate\Contracts\Auth\StatefulGuard
     */
    public $guard;

    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  bool  $remember
     * @param  \Illuminate\Contracts\Auth\StatefulGuard  $guard
     * @return void
     */
    public function __construct($user, $remember, $guard)
    {
        $this->user = $user;
        $this->remember = $remember;
        $this->guard = $guard;
    }
}
