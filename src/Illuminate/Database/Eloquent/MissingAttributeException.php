<?php

namespace Illuminate\Database\Eloquent;

use OutOfBoundsException;

class MissingAttributeException extends OutOfBoundsException
{
    public function __construct($key)
    {
        parent::__construct("The attribute [{$key}] either does not exist or was not retrieved.");
    }
}
