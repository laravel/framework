<?php

namespace Illuminate\Routing\Exceptions;

use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class InvalidSignatureException extends HttpException
{
    /**
     * Create a new exception instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct(Response::HTTP_FORBIDDEN, 'Invalid signature.');
    }
}
