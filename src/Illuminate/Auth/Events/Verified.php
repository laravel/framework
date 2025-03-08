<?php

namespace Illuminate\Auth\Events;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Queue\SerializesModels;

class Verified
{
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Contracts\Auth\MustVerifyEmail  $user  The verified user.
     * @return void
     */
    public function __construct(
        public MustVerifyEmail $user,
    ) {
    }
}
