<?php

namespace Illuminate\Contracts\Encryption;

interface Factory
{
    /**
     * Get a cache store instance by name.
     *
     * @param  string|null  $name
     * @return \Illuminate\Contracts\Encryption\Encrypter
     */
    public function encrypter($name = null);
}
