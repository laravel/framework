<?php

namespace Illuminate\Support\Facades;

use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Support\Testing\Fakes\GateFake;

/**
 * @method static bool has(string|array $ability)
 * @method static \Illuminate\Auth\Access\Response allowIf(\Illuminate\Auth\Access\Response|\Closure|bool $condition, string|null $message = null, string|null $code = null)
 * @method static \Illuminate\Auth\Access\Response denyIf(\Illuminate\Auth\Access\Response|\Closure|bool $condition, string|null $message = null, string|null $code = null)
 * @method static \Illuminate\Auth\Access\Gate define(\UnitEnum|string $ability, callable|array|string $callback)
 * @method static \Illuminate\Auth\Access\Gate resource(string $name, string $class, array|null $abilities = null)
 * @method static \Illuminate\Auth\Access\Gate policy(string $class, string $policy)
 * @method static \Illuminate\Auth\Access\Gate before(callable $callback)
 * @method static \Illuminate\Auth\Access\Gate after(callable $callback)
 * @method static bool allows(iterable|\UnitEnum|string $ability, array|mixed $arguments = [])
 * @method static bool denies(iterable|\UnitEnum|string $ability, array|mixed $arguments = [])
 * @method static bool check(iterable|\UnitEnum|string $abilities, array|mixed $arguments = [])
 * @method static bool any(iterable|\UnitEnum|string $abilities, array|mixed $arguments = [])
 * @method static bool none(iterable|\UnitEnum|string $abilities, array|mixed $arguments = [])
 * @method static \Illuminate\Auth\Access\Response authorize(\UnitEnum|string $ability, array|mixed $arguments = [])
 * @method static \Illuminate\Auth\Access\Response inspect(\UnitEnum|string $ability, array|mixed $arguments = [])
 * @method static mixed raw(string $ability, array|mixed $arguments = [])
 * @method static mixed getPolicyFor(object|string $class)
 * @method static \Illuminate\Auth\Access\Gate guessPolicyNamesUsing(callable $callback)
 * @method static mixed resolvePolicy(object|string $class)
 * @method static \Illuminate\Auth\Access\Gate forUser(\Illuminate\Contracts\Auth\Authenticatable|mixed $user)
 * @method static array abilities()
 * @method static array policies()
 * @method static \Illuminate\Auth\Access\Gate defaultDenialResponse(\Illuminate\Auth\Access\Response $response)
 * @method static \Illuminate\Auth\Access\Gate setContainer(\Illuminate\Contracts\Container\Container $container)
 * @method static \Illuminate\Auth\Access\Response denyWithStatus(int $status, string|null $message = null, int|null $code = null)
 * @method static \Illuminate\Auth\Access\Response denyAsNotFound(string|null $message = null, int|null $code = null)
 * @method static void assertChecked(string $ability, callable|int|null $callback = null)
 * @method static void assertNotChecked(string $ability, callable|null $callback = null)
 * @method static void assertNothingChecked()
 * @method static void assertCheckedTimes(string $ability, int $times = 1)
 * @method static void assertCheckedWith(string $ability, mixed...$arguments)
 * @method static void assertCheckedInOrder(array $abilities)
 * @method static void assertCheckedForUser(mixed $user, string|null $ability = null)
 *
 * @see \Illuminate\Auth\Access\Gate
 */
class Gate extends Facade
{
    /**
     * Replace the bound instance with a fake.
     */
    public static function fake(array $abilities = []): GateFake
    {
        return tap(
            new GateFake(static::getFacadeRoot(), $abilities),
            static function ($fake): void {
                static::swap($fake);
            }
        );
    }

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
