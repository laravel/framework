<?php

namespace Illuminate\Routing\Exceptions;

use Exception;
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
        parent::__construct(401, 'Invalid signature.');
    }
}
