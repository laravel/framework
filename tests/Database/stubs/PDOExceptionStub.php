<?php

class PDOExceptionStub extends PDOException {

    /**
     * Overrides Exception::__construct, which casts $code to integer, so that we can create
     * an exception with a string $code consistent with the real PDOException behavior.
     * 
     * @param  string|null  $message
     * @param  string|null  $code
     * @return void
     */
    public function __construct($message = null, $code = null)
    {
        $this->message = $message;
        $this->code = $code;
    }
}
