<?php

namespace Illuminate\Foundation\Auth\Access;

use Illuminate\Contracts\Auth\Access\Gate;
use Symfony\Component\HttpKernel\Exception\HttpException;

trait AuthorizesRequests
{
    /**
     * Authorize a given action against a set of arguments.
     *
     * @param  mixed  $ability
     * @param  mixed|array  $arguments
     * @return void
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function authorize($ability, $arguments = [])
    {
        list($ability, $arguments) = $this->parseAbilityAndArguments($ability, $arguments);

        if (! app(Gate::class)->check($ability, $arguments)) {
            throw $this->createGateUnauthorizedException($ability, $arguments);
        }
    }

    /**
     * Authorize any of the given actions against a set of arguments.
     *
     * @param  array  $abilities
     * @param  mixed|array  $arguments
     * @return void
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function authorizeAny($abilities, $arguments = [])
    {
        if (! app(Gate::class)->checkAny($abilities, $arguments)) {
            throw $this->createGateUnauthorizedException($abilities, $arguments);
        }
    }

    /**
     * Authorize all of the given actions against a set of arguments.
     *
     * @param  array  $abilities
     * @param  mixed|array  $arguments
     * @return void
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function authorizeAll($abilities, $arguments = [])
    {
        if (! app(Gate::class)->checkAll($abilities, $arguments)) {
            throw $this->createGateUnauthorizedException($abilities, $arguments);
        }
    }

    /**
     * Authorize a given action for a user.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|mixed  $user
     * @param  mixed  $ability
     * @param  mixed|array  $arguments
     * @return void
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function authorizeForUser($user, $ability, $arguments = [])
    {
        list($ability, $arguments) = $this->parseAbilityAndArguments($ability, $arguments);

        $result = app(Gate::class)->forUser($user)->check($ability, $arguments);

        if (! $result) {
            throw $this->createGateUnauthorizedException($ability, $arguments);
        }
    }

    /**
     * Authorize any of the given actions for a user.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|mixed  $user
     * @param  array  $abilities
     * @param  mixed|array  $arguments
     * @return void
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function authorizeAnyForUser($user, $abilities, $arguments = [])
    {
        if (! app(Gate::class)->forUser($user)->checkAny($abilities, $arguments)) {
            throw $this->createGateUnauthorizedException($abilities, $arguments);
        }
    }

    /**
     * Authorize all of the given actions for a user.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|mixed  $user
     * @param  array  $abilities
     * @param  mixed|array  $arguments
     * @return void
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function authorizeAllForUser($user, $abilities, $arguments = [])
    {
        if (! app(Gate::class)->forUser($user)->checkAll($abilities, $arguments)) {
            throw $this->createGateUnauthorizedException($abilities, $arguments);
        }
    }

    /**
     * Guesses the ability's name if it wasn't provided.
     *
     * @param  mixed  $ability
     * @param  mixed|array  $arguments
     * @return array
     */
    protected function parseAbilityAndArguments($ability, $arguments)
    {
        if (is_string($ability)) {
            return [$ability, $arguments];
        }

        return [debug_backtrace(false, 3)[2]['function'], $ability];
    }

    /**
     * Throw an unauthorized exception based on gate results.
     *
     * @param  string|array  $ability
     * @param  array  $arguments
     * @return \Symfony\Component\HttpKernel\Exception\HttpException
     */
    protected function createGateUnauthorizedException($ability, $arguments)
    {
        return new HttpException(403, 'This action is unauthorized.');
    }
}
