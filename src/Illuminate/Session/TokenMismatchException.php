<?php

namespace Illuminate\Session;

use Exception;

class TokenMismatchException extends Exception
{
    /**
     * Create a new token mismatch exception.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->message = __('Sorry, your session has expired. Please refresh and try again.');
    }
}
