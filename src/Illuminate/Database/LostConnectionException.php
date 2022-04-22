<?php

namespace Illuminate\Database;

use LogicException;

class LostConnectionException extends LogicException
{
    public function __construct($message, private string $cause)
    {
        parent::__construct($message);
    }

    public function getRootCause()
    {
        return $this->cause;
    }
}
