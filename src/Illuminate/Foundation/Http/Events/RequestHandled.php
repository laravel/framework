<?php

namespace Illuminate\Foundation\Http\Events;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequestHandled
{
    /**
     * Create a new event instance.
     */
    public function __construct(
        public Request $request,
        public Response $response,
    ) {
    }
}
