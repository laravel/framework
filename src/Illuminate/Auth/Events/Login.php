<?php

namespace Illuminate\Auth\Events;

use Illuminate\Queue\SerializesModels;

class Login
{
    use SerializesModels;

    /**
     * The authentication guard name.
     *
     * @var string
     */
    public $guard;

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
     * Indicates if the user was authenticated using a "remember me" cookie.
     *
     * @var bool
     */
    public $recalled;

    /**
     * Create a new event instance.
     *
     * @param  string  $guard
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  bool  $remember
     * @param  bool  $recalled
     * @return void
     */
    public function __construct($guard, $user, $remember, $recalled = false)
    {
        $this->user = $user;
        $this->guard = $guard;
        $this->remember = $remember;
        $this->recalled = $recalled;
    }
}
