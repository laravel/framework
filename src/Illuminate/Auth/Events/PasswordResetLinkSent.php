<?php

namespace Illuminate\Auth\Events;

use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Queue\SerializesModels;

class PasswordResetLinkSent
{
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Contracts\Auth\CanResetPassword  $user  The user instance.
     * @return void
     */
    public function __construct(
        public CanResetPassword $user,
    ) {
    }
}
