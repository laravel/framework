<?php

namespace Illuminate\Auth\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;

class Registered
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user  The authenticated user.
     */
    public function __construct(
        public $user,
    ) {
    }
}
