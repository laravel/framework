<?php

namespace Illuminate\Database\Query;

use InvalidArgumentException;

class IllegalOperatorAndValueException extends InvalidArgumentException
{
    public function __construct()
    {
        parent::__construct('Illegal operator and value combination.');
    }
}
