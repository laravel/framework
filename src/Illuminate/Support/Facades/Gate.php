<?php

namespace Illuminate\Support\Facades;

use Illuminate\Contracts\Auth\Access\Gate as GateContract;

/**
 * @method static bool has(string | array $ability) Determine if a given ability has been defined.
 * @method static $this define(string $ability, callable | string $callback) Define a new ability.
 * @method static $this resource(string $name, string $class, array $abilities) Define abilities for a resource.
 * @method static $this policy(string $class, string $policy) Define a policy class for a given class type.
 * @method static $this before(callable $callback) Register a callback to run before all Gate checks.
 * @method static $this after(callable $callback) Register a callback to run after all Gate checks.
 * @method static bool allows(string $ability, array | mixed $arguments) Determine if the given ability should be granted for the current user.
 * @method static bool denies(string $ability, array | mixed $arguments) Determine if the given ability should be denied for the current user.
 * @method static bool check(\Illuminate\Auth\Access\iterable | string $abilities, array | mixed $arguments) Determine if all of the given abilities should be granted for the current user.
 * @method static bool any(\Illuminate\Auth\Access\iterable | string $abilities, array | mixed $arguments) Determine if any one of the given abilities should be granted for the current user.
 * @method static \Illuminate\Auth\Access\Response authorize(string $ability, array | mixed $arguments) Determine if the given ability should be granted for the current user.
 * @method static mixed getPolicyFor(object | string $class) Get a policy instance for a given class.
 * @method static mixed resolvePolicy(object | string $class) Build a policy class instance of the given type.
 * @method static static forUser(\Illuminate\Contracts\Auth\Authenticatable | mixed $user) Get a gate instance for the given user.
 * @method static array abilities() Get all of the defined abilities.
 * @method static array policies() Get all of the defined policies.
 *
 * @see \Illuminate\Contracts\Auth\Access\Gate
 */
class Gate extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return GateContract::class;
    }
}
