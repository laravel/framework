<?php

namespace Illuminate\Auth\Events;

use Illuminate\Http\Request;

class Lockout
{
    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function __construct(
        public Request $request,
    ) {
    }
}
