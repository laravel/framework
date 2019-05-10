<?php

namespace Illuminate\Http\Exceptions;

use Exception;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PostTooLargeException extends HttpException
{
    /**
     * PostTooLargeException constructor.
     *
     * @param  string|null  $message
     * @param  \Exception|null  $previous
     * @param  array  $headers
     * @param  int  $code
     * @return void
     */
    public function __construct($message = null, Exception $previous = null, array $headers = [], $code = 0)
    {
        parent::__construct(Response::HTTP_REQUEST_ENTITY_TOO_LARGE, $message, $previous, $headers, $code);
    }
}
