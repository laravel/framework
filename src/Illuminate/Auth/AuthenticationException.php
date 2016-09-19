<?php

namespace Illuminate\Auth;

use Exception;
use Illuminate\Support\Collection;

class AuthenticationException extends Exception
{
    protected $guards = [];

    /**
     * Create a new authentication exception.
     *
     * @param string|array $guards
     * @param string $message
     */
    public function __construct($guards = [], $message = 'Unauthenticated.')
    {
        $this->guards = $guards;
        parent::__construct($message);
    }

    public function guards()
    {
        return new Collection($this->guards);
    }
}
