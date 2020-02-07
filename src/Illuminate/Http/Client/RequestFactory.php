<?php

namespace Illuminate\Http\Client;

class RequestFactory
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
        return (new PendingRequest)->{$method}(...$parameters);
    }
}
