<?php

namespace Illuminate\Http\Exceptions;

use Exception;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class ThrottleRequestsException extends TooManyRequestsHttpException
{
    /**
     * Create a new throttle requests exception instance.
     *
     * @param  string|null  $message
     * @param  \Exception|null  $previous
     * @param  array  $headers
     * @param  int  $code
     * @return void
     */
    public function __construct($message = null, Exception $previous = null, array $headers = [], $code = 0)
    {
        parent::__construct(null, $message, $previous, $code, $headers);
    }
}
