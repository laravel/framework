<?php

namespace Illuminate\Auth\Events;

use Illuminate\Queue\SerializesModels;

class OtherDeviceLogout
{
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  string  $guard  The authentication guard name.
     * @param  \Illuminate\Contracts\Auth\Identity\Identifiable  $user  \Illuminate\Contracts\Auth\Identifiable
     */
    public function __construct(
        public $guard,
        public $user,
    ) {
    }
}
