<?php

namespace Illuminate\Support\Testing\Fakes;

use Illuminate\Contracts\Validation\UncompromisedVerifier;

class UncompromisedVerifierFake implements UncompromisedVerifier
{
    /**
     * Verify that the given data has not been compromised in data leaks.
     *
     * @param  array  $data
     * @return bool
     */
    public function verify($data)
    {
        return true;
    }
}
