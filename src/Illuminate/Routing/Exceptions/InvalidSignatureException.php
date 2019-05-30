<?php

namespace Illuminate\Routing\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class InvalidSignatureException extends HttpException
{
    /**
     * Create a new exception instance.
     *
     * @param string $message
     */
    public function __construct($message = 'Invalid signature.')
    {
        parent::__construct(403, $message);
    }

    /**
     * Create a new exception for an invalid signature.
     *
     * @return \Illuminate\Routing\Exceptions\InvalidSignatureException
     */
    public static function forInvalidSignature()
    {
        return new self('Invalid signature.');
    }

    /**
     * Create a new exception for an expired link.
     *
     * @return \Illuminate\Routing\Exceptions\InvalidSignatureException
     */
    public static function forExpiredLink()
    {
        return new self('Link has expired.');
    }
}
