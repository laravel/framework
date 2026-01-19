<?php

namespace Illuminate\Contracts\Auth\Providers;

use Illuminate\Contracts\Auth\Identity\Identifiable;

/**
 * @template-covariant TUser of Identifiable
 */
interface CredentialsUserProvider
{
    /**
     * Retrieve a user by the given credentials.
     *
     * @param  array  $credentials
     * @return TUser|null
     */
    public function retrieveByCredentials(#[\SensitiveParameter] array $credentials);

    /**
     * Validate a user against the given credentials.
     *
     * @param  TUser  $user
     * @param  array  $credentials
     * @return bool
     *
     * @throws \InvalidArgumentException
     */
    public function validateCredentials(Identifiable $user, #[\SensitiveParameter] array $credentials);

    /**
     * Rehash the user's password if required and supported.
     *
     * @param  TUser  $user
     * @param  array  $credentials
     * @param  bool  $force
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    public function rehashPasswordIfRequired(Identifiable $user, #[\SensitiveParameter] array $credentials, bool $force = false);
}
