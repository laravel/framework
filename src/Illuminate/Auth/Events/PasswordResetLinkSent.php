<?php

namespace Illuminate\Auth\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;

class PasswordResetLinkSent
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Contracts\Auth\CanResetPassword  $user  The user instance.
     */
    public function __construct(
        public $user,
    ) {
    }
}
