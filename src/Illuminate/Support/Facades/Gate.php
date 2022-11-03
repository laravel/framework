<?php

namespace Illuminate\Support\Facades;

use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Support\Testing\Fakes\GateFake;

/**
 * @method static \Illuminate\Auth\Access\Gate guessPolicyNamesUsing(callable $callback)
 * @method static \Illuminate\Auth\Access\Response authorize(string $ability, array|mixed $arguments = [])
 * @method static \Illuminate\Auth\Access\Response inspect(string $ability, array|mixed $arguments = [])
 * @method static \Illuminate\Auth\Access\Response allowIf(\Closure|bool $condition, string|null $message = null, mixed $code = null)
 * @method static \Illuminate\Auth\Access\Response denyIf(\Closure|bool $condition, string|null $message = null, mixed $code = null)
 * @method static \Illuminate\Contracts\Auth\Access\Gate after(callable $callback)
 * @method static \Illuminate\Contracts\Auth\Access\Gate before(callable $callback)
 * @method static \Illuminate\Contracts\Auth\Access\Gate define(string $ability, callable|string $callback)
 * @method static \Illuminate\Contracts\Auth\Access\Gate forUser(\Illuminate\Contracts\Auth\Authenticatable|mixed $user)
 * @method static \Illuminate\Contracts\Auth\Access\Gate policy(string $class, string $policy)
 * @method static array abilities()
 * @method static bool allows(string $ability, array|mixed $arguments = [])
 * @method static bool any(iterable|string $abilities, array|mixed $arguments = [])
 * @method static bool check(iterable|string $abilities, array|mixed $arguments = [])
 * @method static bool denies(string $ability, array|mixed $arguments = [])
 * @method static bool has(string $ability)
 * @method static mixed getPolicyFor(object|string $class)
 * @method static mixed raw(string $ability, array|mixed $arguments = [])
 * @method static \Illuminate\Support\Testing\Fakes\GateFake fail(string $policy, \Illuminate\Auth\Access\Response|\Illuminate\Database\Eloquent\Factories\Sequence|bool|null $value)
 * @method static \Illuminate\Support\Testing\Fakes\GateFake except(string $policy, ?string $ability)
 * @method static \Illuminate\Support\Testing\Fakes\GateFake checkOriginalGate()
 * @method static void assertChecked(string $policy, ?string $ability, ?callable $callback)
 * @method static void assertCheckedTimes(string $policy, string $ability, int $times)
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

    /**
     * @return \Illuminate\Support\Testing\Fakes\GateFake
     */
    public static function fake()
    {
        static::swap($fake = new GateFake(static::getFacadeRoot(), static::$app));

        return $fake;
    }
}
