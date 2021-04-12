<?php

namespace Illuminate\Contracts\Validation;

interface NotCompromisedVerifier
{
    /**
     * Verify that the given value has not been compromised in data leaks.
     *
     * @param  string  $value
     * @return bool
     */
    public function verify($value);
}
