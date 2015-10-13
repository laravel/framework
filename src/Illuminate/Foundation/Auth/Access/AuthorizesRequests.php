<?php

namespace Illuminate\Foundation\Auth\Access;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Auth\Access\UnauthorizedException;
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

        $this->authorizeAtGate(app(Gate::class), $ability, $arguments);
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

        $gate = app(Gate::class)->forUser($user);

        $this->authorizeAtGate($gate, $ability, $arguments);
    }

    /**
     * Authorize the request at the given gate.
     *
     * @param  \Illuminate\Contracts\Auth\Access\Gate  $gate
     * @param  mixed  $ability
     * @param  mixed|array  $arguments
     * @return void
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function authorizeAtGate(Gate $gate, $ability, $arguments)
    {
        try {
            $gate->authorize($ability, $arguments);
        } catch (UnauthorizedException $e) {
            throw $this->createGateUnauthorizedException(
                $ability, $arguments, $e->getMessage()
            );
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
     * @param  string  $ability
     * @param  mixed|array  $arguments
     * @param  string  $message
     * @return \Symfony\Component\HttpKernel\Exception\HttpException
     */
    protected function createGateUnauthorizedException($ability, $arguments, $message = 'This action is unauthorized.')
    {
        return new HttpException(403, $message);
    }
}
