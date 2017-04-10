<?php

namespace Illuminate\Auth\Access;

use Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AuthorizationException extends Exception
{
    /**
     * Prepare the exception for rendering.
     *
     * @return \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function prepareException()
    {
        return new HttpException(403, $this->getMessage());
    }
}
