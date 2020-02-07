<?php

namespace Illuminate\Support\Facades;

use Illuminate\Http\Client\RequestFactory;

/**
 * @see \Illuminate\Http\Client\Client
 */
class Http extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return RequestFactory::class;
    }
}
