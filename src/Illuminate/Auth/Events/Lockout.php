<?php

namespace Illuminate\Auth\Events;

use Illuminate\Http\Request;

class Lockout
{
    /**
     * Create a new event instance.
     */
    public function __construct(
        public Request $request,
    ) {
    }
}
