<?php

namespace Illuminate\Contracts\Auth\Access;

use Illuminate\Auth\Access\Response;

interface Gate
{
    /**
     * Define a new ability.
     *
     * @param  string  $ability
     * @param  callable|string  $callback
     * @return $this
     */
    public function define(string $ability, mixed $callback): static;

    /**
     * Determine if a given ability has been defined.
     */
    public function has(iterable|string $ability): bool;

    /**
     * Define abilities for a resource.
     */
    public function resource(string $name, string $class, array $abilities = null): static;

    /**
     * Define a policy class for a given class type.
     */
    public function policy(string $class, string $policy): static;

    /**
     * Register a callback to run before all Gate checks.
     */
    public function before(callable $callback): static;

    /**
     * Determine if the given ability should be granted for the current user.
     *
     * @param  array|mixed  $arguments
     */
    public function allows(string $ability, mixed $arguments = []): bool;

    /**
     * Determine if the given ability should be denied for the current user.
     *
     * @param  array|mixed  $arguments
     */
    public function denies(string $ability, mixed $arguments = []): bool;

    /**
     * Determine if all of the given abilities should be granted for the current user.
     *
     * @param  array|mixed  $arguments
     */
    public function check(iterable|string $abilities, mixed $arguments = []): bool;

    /**
     * Determine if any one of the given abilities should be granted for the current user.
     *
     * @param  array|mixed  $arguments
     */
    public function any(iterable|string $abilities, mixed $arguments = []): bool;

    /**
     * Register a callback to run after all Gate checks.
     *
     */
    public function after(callable $callback): static;

    /**
     * Determine if the given ability should be granted for the current user.
     *
     * @param  array|mixed  $arguments
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function authorize(string $ability, mixed $arguments = []): Response;

    /**
     * Inspect the user for the given ability.
     *
     * @param  array|mixed  $arguments
     */
    public function inspect(string $ability, mixed $arguments = []): Response;

    /**
     * Get the raw result from the authorization callback.
     *
     * @param  array|mixed  $arguments
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function raw(string $ability, mixed $arguments = []): mixed;

    /**
     * Get a policy instance for a given class.
     *
     * @param  object|string  $class
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public function getPolicyFor(object|string $class);

    /**
     * Get a guard instance for the given user.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|mixed  $user
     */
    public function forUser($user): static;

    /**
     * Get all of the defined abilities.
     *
     * @return array<string, string>
     */
    public function abilities(): array;
}
