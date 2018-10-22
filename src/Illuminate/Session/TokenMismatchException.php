<?php

namespace Illuminate\Session;

use Exception;

class TokenMismatchException extends Exception
{
    protected $message = 'The page has expired due to inactivity.';
}
