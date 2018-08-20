<?php

namespace Illuminate\Auth\Events;

use Illuminate\Queue\SerializesModels;

class Logout
{
    use SerializesModels;

    /**
     * The authenticationg guard implementation.
     *
     * @var \Illuminate\Contracts\Auth\StatefulGuard
     */
    public $guard;

    /**
     * The authenticated user.
     *
     * @var \Illuminate\Contracts\Auth\Authenticatable
     */
    public $user;

    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Contracts\Auth\StatefulGuard  $guard
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return void
     */
    public function __construct($guard, $user)
    {
        $this->user = $user;
        $this->guard = $guard;
    }
}
