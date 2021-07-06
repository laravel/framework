<?php

namespace Illuminate\Foundation\Http\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class MissingIdempotencyKeyException extends HttpException
{
    /**
     * Create a new "idempotency key mismatch" exception instance.
     *
     * @param  string|null  $message
     * @param  \Throwable|null  $previous
     * @param  array  $headers
     * @param  int  $code
     * @return void
     */
    public function __construct($message = null, Throwable $previous = null, array $headers = [], $code = 0)
    {
        parent::__construct(400, $message, $previous, $headers, $code);
    }
}
