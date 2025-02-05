<?php

namespace Illuminate\Http\Client\Events;

use Illuminate\Http\Client\Request;

class RequestSending
{
    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Http\Client\Request  $request  The request instance.
     * @return void
     */
    public function __construct(
        public Request $request,
    ) {
    }
}
