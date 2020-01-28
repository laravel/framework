<?php

namespace Illuminate\Support\Facades;

use Illuminate\Contracts\Auth\Access\Gate as GateContract;

/**
 * @method static bool has(string $ability)
 * @method static \Illuminate\Contracts\Auth\Access\Gate define(string $ability, callable|string $callback)
 * @method static \Illuminate\Contracts\Auth\Access\Gate policy(string $class, string $policy)
 * @method static \Illuminate\Contracts\Auth\Access\Gate before(callable $callback)
 * @method static \Illuminate\Contracts\Auth\Access\Gate after(callable $callback)
 * @method static bool allows(string $ability, mixed $arguments = [])
 * @method static bool denies(string $ability, mixed $arguments = [])
 * @method static bool check(iterable|string $abilities, mixed $arguments = [])
 * @method static bool any(iterable|string $abilities, mixed $arguments = [])
 * @method static \Illuminate\Auth\Access\Response authorize(string $ability, mixed $arguments = [])
 * @method static mixed raw(string $ability, mixed $arguments = [])
 * @method static mixed getPolicyFor(object|string $class)
 * @method static \Illuminate\Contracts\Auth\Access\Gate forUser(mixed $user)
 * @method static array abilities()
 * @method static \Illuminate\Auth\Access\Response inspect(string $ability, mixed $arguments = [])
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
