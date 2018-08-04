<?php

namespace Illuminate\Hashing;

class Argon2IdHasher extends ArgonHasher
{
    /**
     * Get the algorithm that should be used for hashing.
     *
     * @return string
     */
    protected function algorithm()
    {
        return PASSWORD_ARGON2ID;
    }
}
