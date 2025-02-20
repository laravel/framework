<?php

namespace Illuminate\Routing\Events;

class Routing
{
    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Http\Request  $request  The request instance.
     * @return void
     */
    public function __construct(
        public $request,
    ) {
    }
}
