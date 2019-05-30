<?php

namespace Illuminate\Routing\Exceptions;

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
        parent::__construct(403, 'Invalid signature.');
    }

    public static function forInvalidSignature()
    {
        return new parent(403, 'Invalid signature.');
    }

    public static function forExpiredLink()
    {
        return new parent(403, 'Link has expired.');
    }
}
