<?php

namespace Illuminate\Contracts\Auth\Access;

interface Authorizable
{
    /**
     * Determine if the entity has a given ability.
     *
     * @param  string  $ability
     * @param  mixed  $arguments
     * @return bool
     */
    public function can($ability, $arguments = []);
}
