<?php

namespace Illuminate\Auth\Eloquent;

use Illuminate\Database\Eloquent\Model;

/**
 * @mixin Model
 */
trait EloquentHasAuthPassword
{
    /**
     * The column name of the password field using during authentication.
     *
     * @var string
     */
    protected $authPasswordName = 'password';

    /**
     * Get the name of the password attribute for the user.
     *
     * @return string
     */
    public function getAuthPasswordName()
    {
        return $this->authPasswordName;
    }

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPassword()
    {
        return $this->{$this->getAuthPasswordName()};
    }
}
