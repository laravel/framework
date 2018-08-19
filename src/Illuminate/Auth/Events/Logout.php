<?php

namespace Illuminate\Auth\Events;

use Illuminate\Queue\SerializesModels;

class Logout
{
    use SerializesModels;

    /**
     * The authenticated user.
     *
     * @var \Illuminate\Contracts\Auth\Authenticatable
     */
    public $user;

    /**
     * The guard to which the user was authenticated.
     *
     * @var string
     */
    public $guard;

    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string  $guard
     * @return void
     */
    public function __construct($user, $guard)
    {
        $this->user = $user;
        $this->guard = $guard;
    }
}
