<?php

namespace Illuminate\Auth\Events;

class Attempting
{
    /**
     * Create a new event instance.
     *
     * @param  string  $guard  The authentication guard name.
     * @param  array  $credentials  The credentials for the user.
     * @param  bool  $remember  Indicates if the user should be "remembered".
     * @return void
     */
    public function __construct(
        public $guard,
        #[\SensitiveParameter] public $credentials,
        public $remember,
    ) {
    }
}
