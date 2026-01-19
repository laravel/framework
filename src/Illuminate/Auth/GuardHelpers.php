<?php

namespace Illuminate\Auth;

use Illuminate\Contracts\Auth\Identity\Identifiable as IdentifiableContract;

/**
 * These methods are typically the same across all guards.
 *
 * @template TUser of IdentifiableContract
 */
trait GuardHelpers
{
    /**
     * The currently authenticated user.
     *
     * @var TUser|null
     */
    protected $user;

    /**
     * Determine if the current user is authenticated. If not, throw an exception.
     *
     * @return TUser
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function authenticate()
    {
        return $this->user() ?? throw new AuthenticationException;
    }

    /**
     * Determine if the guard has a user instance.
     *
     * @return bool
     */
    public function hasUser()
    {
        return ! is_null($this->user);
    }

    /**
     * Determine if the current user is authenticated.
     *
     * @return bool
     */
    public function check()
    {
        return ! is_null($this->user());
    }

    /**
     * Determine if the current user is a guest.
     *
     * @return bool
     */
    public function guest()
    {
        return ! $this->check();
    }

    /**
     * Get the ID for the currently authenticated user.
     *
     * @return int|string|null
     */
    public function id()
    {
        if ($this->user()) {
            return $this->user()->getAuthIdentifier();
        }
    }

    /**
     * Set the current user.
     *
     * @param  TUser  $user
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function setUser(IdentifiableContract $user)
    {
        $this->assertUserType($user);

        $this->user = $user;

        return $this;
    }

    /**
     * Forget the current user.
     *
     * @return $this
     */
    public function forgetUser()
    {
        $this->user = null;

        return $this;
    }
}
