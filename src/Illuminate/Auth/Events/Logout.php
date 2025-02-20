<?php

namespace Illuminate\Auth\Events;

use Illuminate\Queue\SerializesModels;

class Logout
{
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  string  $guard  The authentication guard name.
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user  The authenticated user.
     * @return void
     */
    public function __construct(
        public $guard,
        public $user,
    ) {
    }
}
