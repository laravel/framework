<?php

namespace Illuminate\Auth\Events;

use Illuminate\Contracts\Auth\Authenticatable;

class Failed
{
    /**
     * Create a new event instance.
     *
     * @param  string  $guard  The authentication guard name.
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $user  The user the attempter was trying to authenticate as.
     * @param  array  $credentials  The credentials provided by the attempter.
     * @return void
     */
    public function __construct(
        public string $guard,
        public ?Authenticatable $user,
        #[\SensitiveParameter] public array $credentials,
    ) {
    }
}
