<?php

namespace Illuminate\Support\Facades;

/**
 * @method static \Illuminate\Contracts\Auth\Guard|\Illuminate\Contracts\Auth\StatefulGuard guard(string $name) Attempt to get the guard from the local cache.
 * @method static \Illuminate\Auth\SessionGuard createSessionDriver(string $name, array $config) Create a session based authentication guard.
 * @method static \Illuminate\Auth\TokenGuard createTokenDriver(string $name, array $config) Create a token based authentication guard.
 * @method static string getDefaultDriver() Get the default authentication driver name.
 * @method static void shouldUse(string $name) Set the default guard driver the factory should serve.
 * @method static void setDefaultDriver(string $name) Set the default authentication driver name.
 * @method static $this viaRequest(string $driver, callable $callback) Register a new callback based request guard.
 * @method static \Closure userResolver() Get the user resolver callback.
 * @method static $this resolveUsersUsing(\Closure $userResolver) Set the callback to be used to resolve users.
 * @method static $this extend(string $driver, \Closure $callback) Register a custom driver creator Closure.
 * @method static $this provider(string $name, \Closure $callback) Register a custom provider creator Closure.
 * @method static \Illuminate\Contracts\Auth\UserProvider|null createUserProvider(string | null $provider) Create the user provider implementation for the driver.
 * @method static string getDefaultUserProvider() Get the default user provider name.
 * @method static \Illuminate\Foundation\Auth\User|null user() Get the currently authenticated user.
 * @method static int|null id() Get the ID for the currently authenticated user.
 * @method static bool once(array $credentials) Log a user into the application without sessions or cookies.
 * @method static \Illuminate\Foundation\Auth\User|false onceUsingId(mixed $id) Log the given user ID into the application without sessions or cookies.
 * @method static bool validate(array $credentials) Validate a user's credentials.
 * @method static Response|null basic(string $field, array $extraConditions) Attempt to authenticate using HTTP Basic Auth.
 * @method static Response|null onceBasic(string $field, array $extraConditions) Perform a stateless HTTP Basic login attempt.
 * @method static bool attempt(array $credentials, bool $remember) Attempt to authenticate a user using the given credentials.
 * @method static \Illuminate\Foundation\Auth\User|false loginUsingId(mixed $id, bool $remember) Log the given user ID into the application.
 * @method static void login(\Illuminate\Contracts\Auth\Authenticatable $user, bool $remember) Log a user into the application.
 * @method static void logout() Log the user out of the application.
 * @method static void attempting(mixed $callback) Register an authentication attempt event listener.
 * @method static \Illuminate\Foundation\Auth\User getLastAttempted() Get the last user we attempted to authenticate.
 * @method static string getName() Get a unique identifier for the auth session value.
 * @method static string getRecallerName() Get the name of the cookie used to store the "recaller".
 * @method static bool viaRemember() Determine if the user was authenticated via "remember me" cookie.
 * @method static \Illuminate\Contracts\Cookie\QueueingFactory getCookieJar() Get the cookie creator instance used by the guard.
 * @method static void setCookieJar(\Illuminate\Contracts\Cookie\QueueingFactory $cookie) Set the cookie creator instance used by the guard.
 * @method static \Illuminate\Contracts\Events\Dispatcher getDispatcher() Get the event dispatcher instance.
 * @method static void setDispatcher(\Illuminate\Contracts\Events\Dispatcher $events) Set the event dispatcher instance.
 * @method static \Illuminate\Contracts\Session\Session. getSession() Get the session store used by the guard.
 * @method static \Illuminate\Foundation\Auth\User|null getUser() Return the currently cached user.
 * @method static $this setUser(\Illuminate\Contracts\Auth\Authenticatable $user) Set the current user.
 * @method static Request getRequest() Get the current request instance.
 * @method static Request $request) Set the current request instance.
 * @method static \Illuminate\Foundation\Auth\User authenticate() Determine if the current user is authenticated.
 * @method static bool check() Determine if the current user is authenticated.
 * @method static bool guest() Determine if the current user is a guest.
 * @method static \Illuminate\Contracts\Auth\UserProvider getProvider() Get the user provider used by the guard.
 * @method static void setProvider(\Illuminate\Contracts\Auth\UserProvider $provider) Set the user provider used by the guard.
 * @method static void macro(string $name, object | callable $macro) Register a custom macro.
 * @method static void mixin(object $mixin) Mix another object into the class.
 * @method static bool hasMacro(string $name) Checks if macro is registered.
 *
 * @see \Illuminate\Auth\AuthManager
 * @see \Illuminate\Contracts\Auth\Factory
 * @see \Illuminate\Contracts\Auth\Guard
 * @see \Illuminate\Contracts\Auth\StatefulGuard
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
     * @return void
     */
    public static function routes()
    {
        static::$app->make('router')->auth();
    }
}
