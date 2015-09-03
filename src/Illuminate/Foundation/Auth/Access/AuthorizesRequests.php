<?php

namespace Illuminate\Foundation\Auth\Access;

use Illuminate\Contracts\Auth\Access\Gate;
use Symfony\Component\HttpKernel\Exception\HttpException;

trait AuthorizesRequests
{
    /**
     * Authorize a given action against a set of arguments.
     *
     * @param  string  $ability
     * @param  mixed|array  $arguments
     * @return void
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function authorize($ability, $arguments = [])
    {
        if (! is_string($ability)) {
            list($arguments, $ability) = [$ability, $this->guessAbilityName()];
        }

        if (! app(Gate::class)->check($ability, $arguments)) {
            throw $this->createGateUnauthorizedException($ability, $arguments);
        }
    }

    /**
     * Authorize a given action for a user.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|mixed  $user
     * @param  string  $ability
     * @param  array  $arguments
     * @return void
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function authorizeForUser($user, $ability, $arguments = [])
    {
        if (! is_string($ability)) {
            list($arguments, $ability) = [$ability, $this->guessAbilityName()];
        }

        $result = app(Gate::class)->forUser($user)->check($ability, $arguments);

        if (! $result) {
            throw $this->createGateUnauthorizedException($ability, $arguments);
        }
    }

    /**
     * Guesses the ability's name from the original method name.
     *
     * @return string
     */
    protected function guessAbilityName()
    {
        return debug_backtrace(false, 3)[2]['function'];
    }

    /**
     * Throw an unauthorized exception based on gate results.
     *
     * @param  string  $ability
     * @param  array  $arguments
     * @return void
     */
    protected function createGateUnauthorizedException($ability, $arguments)
    {
        return new HttpException(403, 'This action is unauthorized.');
    }
}
