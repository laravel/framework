<?php

namespace Illuminate\Encryption;

class MissingKeyException extends \RuntimeException
{
    public function __construct($message = 'No application encryption key has been specified.')
    {
        parent::__construct($message);
    }
}
