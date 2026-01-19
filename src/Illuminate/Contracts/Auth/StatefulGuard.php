<?php

namespace Illuminate\Contracts\Auth;

use Illuminate\Contracts\Auth\Identity\StatefulIdentifiable;

/**
 * Persistent credentials-based authentication (passwords, tokens) authentication.
 *
 * @template-covariant TUser of StatefulIdentifiable
 *
 * @extends Guard<TUser>
 */
interface StatefulGuard extends Guard
{
    /**
     * Attempt to authenticate a user using the given credentials.
     *
     * @param  array  $credentials
     * @param  bool  $remember
     * @return bool
     */
    public function attempt(array $credentials = [], $remember = false);

    /**
     * Log a user into the application without sessions or cookies.
     *
     * @param  array  $credentials
     * @return bool
     */
    public function once(array $credentials = []);

    /**
     * Log a user into the application.
     *
     * @param  TUser  $user
     * @param  bool  $remember
     * @return void
     */
    public function login(StatefulIdentifiable $user, $remember = false);

    /**
     * Log the given user ID into the application.
     *
     * @param  mixed  $id
     * @param  bool  $remember
     * @return TUser|false
     */
    public function loginUsingId($id, $remember = false);

    /**
     * Log the given user ID into the application without sessions or cookies.
     *
     * @param  mixed  $id
     * @return TUser|false
     */
    public function onceUsingId($id);

    /**
     * Determine if the user was authenticated via "remember me" cookie.
     *
     * @return bool
     */
    public function viaRemember();

    /**
     * Log the user out of the application.
     *
     * @return void
     */
    public function logout();
}
