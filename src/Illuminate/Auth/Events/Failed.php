<?php

namespace Illuminate\Auth\Events;

class Failed
{
    /**
     * Create a new event instance.
     *
     * @param  string  $guard
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $user
     * @param  array  $credentials
     * @return void
     */
    public function __construct(
        public $guard,
        public $user,
        #[\SensitiveParameter] public $credentials,
    ) {
    }
}
