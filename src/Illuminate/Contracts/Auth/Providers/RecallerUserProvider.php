<?php

namespace Illuminate\Contracts\Auth\Providers;

use Illuminate\Contracts\Auth\Identity\Identifiable;

/**
 * @template-covariant TUser of Identifiable
 */
interface RecallerUserProvider
{
    /**
     * Retrieve a user by their unique identifier and "remember me" token.
     *
     * @param  mixed  $identifier
     * @param  string  $token
     * @return TUser|null
     */
    public function retrieveByToken($identifier, #[\SensitiveParameter] $token);

    /**
     * Update the "remember me" token for the given user in storage.
     *
     * @param  TUser  $user
     * @param  string  $token
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    public function updateRememberToken(Identifiable $user, #[\SensitiveParameter] $token);
}
