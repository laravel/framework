<?php

namespace Illuminate\Support\Facades;

use Laravel\Ui\UiServiceProvider;
use RuntimeException;

/**
 * @method static \Illuminate\Contracts\Auth\Guard|\Illuminate\Contracts\Auth\StatefulGuard guard(string|null $name = null)
 * @method static \Illuminate\Auth\SessionGuard createSessionDriver(string $name, array $config)
 * @method static \Illuminate\Auth\TokenGuard createTokenDriver(string $name, array $config)
 * @method static string getDefaultDriver()
 * @method static void shouldUse(string $name)
 * @method static void setDefaultDriver(string $name)
 * @method static \Illuminate\Auth\AuthManager viaRequest(string $driver, callable $callback)
 * @method static \Closure userResolver()
 * @method static \Illuminate\Auth\AuthManager resolveUsersUsing(\Closure $userResolver)
 * @method static \Illuminate\Auth\AuthManager extend(string $driver, \Closure $callback)
 * @method static \Illuminate\Auth\AuthManager provider(string $name, \Closure $callback)
 * @method static bool hasResolvedGuards()
 * @method static \Illuminate\Auth\AuthManager forgetGuards()
 * @method static \Illuminate\Auth\AuthManager setApplication(\Illuminate\Contracts\Foundation\Application $app)
 * @method static \Illuminate\Contracts\Auth\Providers\StatefulUserProvider|null createUserProvider(string|null $provider = null)
 * @method static string getDefaultUserProvider()
 * @method static bool check()
 * @method static bool guest()
 * @method static \Illuminate\Contracts\Auth\Identity\Identifiable|null user()
 * @method static int|string|null id()
 * @method static bool validate(array $credentials = [])
 * @method static bool hasUser()
 * @method static \Illuminate\Contracts\Auth\Guard setUser(\Illuminate\Contracts\Auth\Identity\Identifiable $user)
 * @method static bool attempt(array $credentials = [], bool $remember = false)
 * @method static bool once(array $credentials = [])
 * @method static void login(\Illuminate\Contracts\Auth\Identity\StatefulIdentifiable $user, bool $remember = false)
 * @method static \Illuminate\Contracts\Auth\Identity\StatefulIdentifiable|false loginUsingId(mixed $id, bool $remember = false)
 * @method static \Illuminate\Contracts\Auth\Identity\StatefulIdentifiable|false onceUsingId(mixed $id)
 * @method static bool viaRemember()
 * @method static void logout()
 * @method static \Symfony\Component\HttpFoundation\Response|null basic(string $field = 'email', array $extraConditions = [])
 * @method static \Symfony\Component\HttpFoundation\Response|null onceBasic(string $field = 'email', array $extraConditions = [])
 * @method static bool attemptWhen(array $credentials = [], array|callable|null $callbacks = null, bool $remember = false)
 * @method static void logoutCurrentDevice()
 * @method static \Illuminate\Contracts\Auth\Identity\StatefulIdentifiable|null logoutOtherDevices(string $password)
 * @method static void attempting(mixed $callback)
 * @method static \Illuminate\Contracts\Auth\Identity\StatefulIdentifiable getLastAttempted()
 * @method static string getName()
 * @method static string getRecallerName()
 * @method static \Illuminate\Auth\SessionGuard setRememberDuration(int $minutes)
 * @method static \Illuminate\Contracts\Cookie\QueueingFactory getCookieJar()
 * @method static void setCookieJar(\Illuminate\Contracts\Cookie\QueueingFactory $cookie)
 * @method static \Illuminate\Contracts\Events\Dispatcher getDispatcher()
 * @method static void setDispatcher(\Illuminate\Contracts\Events\Dispatcher $events)
 * @method static \Illuminate\Contracts\Session\Session getSession()
 * @method static \Illuminate\Contracts\Auth\Identity\Identifiable|null getUser()
 * @method static \Symfony\Component\HttpFoundation\Request getRequest()
 * @method static \Illuminate\Auth\SessionGuard setRequest(\Symfony\Component\HttpFoundation\Request $request)
 * @method static \Illuminate\Support\Timebox getTimebox()
 * @method static \Illuminate\Contracts\Auth\Identity\Identifiable authenticate()
 * @method static \Illuminate\Auth\SessionGuard forgetUser()
 * @method static \Illuminate\Contracts\Auth\Providers\StatefulUserProvider getProvider()
 * @method static void setProvider(\Illuminate\Contracts\Auth\Providers\StatefulUserProvider $provider)
 * @method static void macro(string $name, object|callable $macro)
 * @method static void mixin(object $mixin, bool $replace = true)
 * @method static bool hasMacro(string $name)
 * @method static void flushMacros()
 *
 * @see \Illuminate\Auth\AuthManager
 * @see \Illuminate\Auth\SessionGuard
 */
class Auth extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'auth';
    }

    /**
     * Register the typical authentication routes for an application.
     *
     * @param  array  $options
     * @return void
     *
     * @throws \RuntimeException
     */
    public static function routes(array $options = [])
    {
        if (! static::$app->providerIsLoaded(UiServiceProvider::class)) {
            throw new RuntimeException('In order to use the Auth::routes() method, please install the laravel/ui package.');
        }

        static::$app->make('router')->auth($options);
    }
}
