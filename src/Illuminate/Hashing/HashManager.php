<?php

namespace Illuminate\Hashing;

use Illuminate\Support\Manager;

class HashManager extends Manager
{
    /**
     * Get the default driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->app['config']['hashing.driver'];
    }

    /**
     * Create an instance of the Brycrypt hash Driver.
     *
     * @return BcryptHasher
     */
    public function createBcryptDriver()
    {
        return new BcryptHasher;
    }

    /**
     * Create an instance of the Argon2 hash Driver.
     *
     * @return ArgonHasher
     */
    public function createArgonDriver()
    {
        return new ArgonHasher;
    }
}