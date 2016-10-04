<?php

namespace Illuminate\Auth;

use Exception;

class AuthenticationException extends Exception
{
    /**
     * Array of the given guards
     *
     * @var array
     */
    protected $guards;

    /**
     * Create a new authentication exception.
     *
     * @param string  $message
     */
    public function __construct($message = 'Unauthenticated.')
    {
        parent::__construct($message);
    }

    /**
     * Set the given guards
     *
     * @param array $guards
     * @return $this
     */
    public function setGuards(array $guards)
    {
        $this->guards = $guards;

        return $this;
    }

    /**
     * Get the given guards
     *
     * @return array
     */
    public function getGuards()
    {
        return $this->guards;
    }
}
