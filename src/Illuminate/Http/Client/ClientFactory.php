<?php

namespace Illuminate\Http\Client;

class ClientFactory
{
    /**
     * Execute a method against a new client instance.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return (new Client)->{$method}(...$parameters);
    }
}
