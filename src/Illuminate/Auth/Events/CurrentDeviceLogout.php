<?php

namespace Illuminate\Auth\Events;

use Illuminate\Queue\SerializesModels;

class CurrentDeviceLogout
{
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  string  $guard
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return void
     */
    public function __construct(
        public $guard,
        public $user,
    ) {
    }
}
