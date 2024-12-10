<?php

namespace Illuminate\Auth\Events;

use Illuminate\Queue\SerializesModels;

class PasswordResetLinkSent
{
    use SerializesModels;

    /**
     * The user instance.
     *
     * @var \Illuminate\Contracts\Auth\CanResetPassword
     */
    public $user;

    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Contracts\Auth\CanResetPassword  $user
     * @return void
     */
    public function __construct($user)
    {
        $this->user = $user;
    }
}
