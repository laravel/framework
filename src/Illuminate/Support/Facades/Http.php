<?php

namespace Illuminate\Support\Facades;

use Illuminate\Http\Client\Factory;
use Illuminate\Support\Testing\Fakes\HttpFake;

/**
 * @mixin \Illuminate\Http\Client\PendingRequest
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
        return Factory::class;
    }

    /**
     * Fake the HTTP client to return mocked responses.
     *
     * @return \Illuminate\Support\Testing\Fakes\HttpFake
     */
    public static function fake()
    {
        static::swap($httpFake = new HttpFake());

        return $httpFake;
    }
}
