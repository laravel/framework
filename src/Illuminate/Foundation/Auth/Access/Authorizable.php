<?php

namespace Illuminate\Foundation\Auth\Access;

use Illuminate\Contracts\Auth\Access\Gate;

trait Authorizable
{
    /**
     * Determine if the entity has a given ability.
     *
     * @param  string  $ability
     * @param  array|mixed  $arguments
     * @return bool
     */
    public function can($ability, $arguments = [])
    {
        return app(Gate::class)->forUser($this)->check($ability, $arguments);
    }

    /**
     * Determine if the entity has any of the given abilities.
     *
     * @param  array  $abilities
     * @param  array|mixed  $arguments
     * @return bool
     */
    public function canAny($abilities, $arguments = [])
    {
        return app(Gate::class)->forUser($this)->checkAny($abilities, $arguments);
    }

    /**
     * Determine if the entity has all of the given abilities.
     *
     * @param  array  $abilities
     * @param  array|mixed  $arguments
     * @return bool
     */
    public function canAll($abilities, $arguments = [])
    {
        return app(Gate::class)->forUser($this)->checkAll($abilities, $arguments);
    }

    /**
     * Determine if the entity does not have a given ability.
     *
     * @param  string  $ability
     * @param  array|mixed  $arguments
     * @return bool
     */
    public function cant($ability, $arguments = [])
    {
        return ! $this->can($ability, $arguments);
    }

    /**
     * Determine if the entity does not have a given ability.
     *
     * @param  string  $ability
     * @param  array|mixed  $arguments
     * @return bool
     */
    public function cannot($ability, $arguments = [])
    {
        return $this->cant($ability, $arguments);
    }
}
