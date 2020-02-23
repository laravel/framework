<?php

namespace Illuminate\Http\Client;

use GuzzleHttp\Client as Guzzle;

class Factory
{
    /**
     * Execute a method against a new pending request instance.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        $pendingRequest = new PendingRequest(new Guzzle);

        return $pendingRequest->{$method}(...$parameters);
    }
}
