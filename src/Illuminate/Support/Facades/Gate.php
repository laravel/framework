<?php

namespace Illuminate\Support\Facades;

use Illuminate\Contracts\Auth\Access\Gate as GateContract;

/**
 * @method static array abilities()
 * @method static \Illuminate\Contracts\Auth\Access\Gate after(callable $callback)
 * @method static \Illuminate\Auth\Access\Response allowIf(\Illuminate\Auth\Access\Response|\Closure|bool $condition, string|null $message = null, string|null $code = null)
 * @method static bool allows(string $ability, array|mixed $arguments = [])
 * @method static bool any(iterable|string $abilities, array|mixed $arguments = [])
 * @method static \Illuminate\Auth\Access\Response authorize(string $ability, array|mixed $arguments = [])
 * @method static \Illuminate\Contracts\Auth\Access\Gate before(callable $callback)
 * @method static bool check(iterable|string $abilities, array|mixed $arguments = [])
 * @method static \Illuminate\Contracts\Auth\Access\Gate define(string $ability, callable|string $callback)
 * @method static bool denies(string $ability, array|mixed $arguments = [])
 * @method static \Illuminate\Auth\Access\Response denyIf(\Illuminate\Auth\Access\Response|\Closure|bool $condition, string|null $message = null, string|null $code = null)
 * @method static static forUser(\Illuminate\Contracts\Auth\Authenticatable|mixed $user)
 * @method static mixed getPolicyFor(object|string $class)
 * @method static \Illuminate\Auth\Access\Gate guessPolicyNamesUsing(callable $callback)
 * @method static bool has(string $ability)
 * @method static \Illuminate\Auth\Access\Response inspect(string $ability, array|mixed $arguments = [])
 * @method static bool none(iterable|string $abilities, array|mixed $arguments = [])
 * @method static array policies()
 * @method static \Illuminate\Contracts\Auth\Access\Gate policy(string $class, string $policy)
 * @method static mixed raw(string $ability, array|mixed $arguments = [])
 * @method static mixed resolvePolicy(object|string $class)
 * @method static \Illuminate\Contracts\Auth\Access\Gate resource(string $name, string $class, array|null $abilities = null)
 * @method static \Illuminate\Auth\Access\Gate setContainer(\Illuminate\Contracts\Container\Container $container)
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
