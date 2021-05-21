<?php

namespace Illuminate\Support\Facades;

use Illuminate\Contracts\Validation\UncompromisedVerifier as UncompromisedVerifierContract;
use Illuminate\Support\Testing\Fakes\UncompromisedVerifierFake;

/**
 * @method static bool verify(array $data)
 *
 * @see \Illuminate\Validation\NotPwnedVerifier
 * @see \Illuminate\Support\Testing\Fakes\UncompromisedVerifierFake
 */
class UncompromisedVerifier extends Facade
{
    /**
     * Replace the bound instance with a fake.
     *
     * @return \Illuminate\Support\Testing\Fakes\UncompromisedVerifierFake
     */
    public static function fake()
    {
        static::swap($fake = new UncompromisedVerifierFake());

        return $fake;
    }

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return UncompromisedVerifierContract::class;
    }
}
