<?php

namespace Illuminate\Contracts\Auth\Providers;

use Illuminate\Contracts\Auth\Identity\Identifiable;

/**
 * @template-covariant TUser of Identifiable
 */
interface BasicUserProvider
{
    /**
     * Retrieve a user by their unique identifier.
     *
     * @param  mixed  $identifier
     * @return TUser|null
     */
    public function retrieveById($identifier);
}
