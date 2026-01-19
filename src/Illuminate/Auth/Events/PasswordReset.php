<?php

namespace Illuminate\Auth\Events;

use Illuminate\Queue\SerializesModels;

class PasswordReset
{
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Contracts\Auth\Identity\Identifiable  $user  The user.
     */
    public function __construct(
        public $user,
    ) {
    }
}
