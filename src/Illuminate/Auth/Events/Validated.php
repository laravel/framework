<?php

namespace Illuminate\Auth\Events;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Queue\SerializesModels;

class Validated
{
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  string  $guard  The authentication guard name.
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user  The user retrieved and validated from the User Provider.
     * @return void
     */
    public function __construct(
        public string $guard,
        public Authenticatable $user,
    ) {
    }
}
