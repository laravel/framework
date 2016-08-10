<?php

namespace Illuminate\Auth\Access;

use Exception;

class AuthorizationException extends Exception
{
    public function __construct($message = 'Unauthorized')
    {
        parent::__construct($message, 401);
    }
}
