<?php

namespace Illuminate\Auth\Access;

use Exception;

class AuthorizationException extends Exception
{
    /**
     * Create a new authorization exception instance.
     *
     * @param  string|null  $message
     * @param  mixed|null  $code
     * @param  \Exception|null  $previous
     * @return void
     */
    public function __construct($message = '', $code = null, Exception $previous = null)
    {
        parent::__construct($message, 0, $previous);

        $this->code = $code;
    }

    /**
     * Create a deny response object from this exception.
     *
     * @return \Illuminate\Auth\Access\Response
     */
    public function toResponse()
    {
        return Response::deny($this->message, $this->code);
    }
}
