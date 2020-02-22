<?php

namespace Illuminate\Broadcasting;

class PrivateEncryptedChannel extends Channel
{
    /**
     * Create a new private encrypted channel instance.
     *
     * @param  string  $name
     * @return void
     */
    public function __construct($name)
    {
        parent::__construct('private-encrypted-'.$name);
    }
}
