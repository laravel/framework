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
    public static function __callStatic($method, $parameters)
    {
        return (new Client)->{$method}(...$parameters);
    }
}
