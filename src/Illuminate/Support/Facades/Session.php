<?php

namespace Illuminate\Support\Facades;

/**
 * @method static array getSessionConfig() Get the session configuration.
 * @method static string getDefaultDriver() Get the default session driver name.
 * @method static void setDefaultDriver(string $name) Set the default session driver name.
 * @method static mixed driver(string $driver) Get a driver instance.
 * @method static $this extend(string $driver, \Closure $callback) Register a custom driver creator Closure.
 * @method static array getDrivers() Get all of the created "drivers".
 * @method static bool start() Start the session, reading the data from a handler.
 * @method static bool save() Save the session data to storage.
 * @method static void ageFlashData() Age the flash data for the session.
 * @method static array all() Get all of the session data.
 * @method static bool exists(string | array $key) Checks if a key exists.
 * @method static bool has(string | array $key) Checks if a key is present and not null.
 * @method static mixed get(string $key, mixed $default) Get an item from the session.
 * @method static mixed pull(string $key, string $default) Get the value of a given key and then forget it.
 * @method static bool hasOldInput(string $key) Determine if the session contains old input.
 * @method static mixed getOldInput(string $key, mixed $default) Get the requested item from the flashed input array.
 * @method static void replace(array $attributes) Replace the given session attributes entirely.
 * @method static void put(string | array $key, mixed $value) Put a key / value pair or array of key / value pairs in the session.
 * @method static mixed remember(string $key, \Closure $callback) Get an item from the session, or store the default value.
 * @method static void push(string $key, mixed $value) Push a value onto a session array.
 * @method static mixed increment(string $key, int $amount) Increment the value of an item in the session.
 * @method static int decrement(string $key, int $amount) Decrement the value of an item in the session.
 * @method static void flash(string $key, mixed $value) Flash a key / value pair to the session.
 * @method static void now(string $key, mixed $value) Flash a key / value pair to the session for immediate use.
 * @method static void reflash() Reflash all of the session flash data.
 * @method static void keep(array | mixed $keys) Reflash a subset of the current flash data.
 * @method static void flashInput(array $value) Flash an input array to the session.
 * @method static mixed remove(string $key) Remove an item from the session, returning its value.
 * @method static void forget(string | array $keys) Remove one or many items from the session.
 * @method static void flush() Remove all of the items from the session.
 * @method static bool invalidate() Flush the session data and regenerate the ID.
 * @method static bool regenerate(bool $destroy) Generate a new session identifier.
 * @method static bool migrate(bool $destroy) Generate a new session ID for the session.
 * @method static bool isStarted() Determine if the session has been started.
 * @method static string getName() Get the name of the session.
 * @method static void setName(string $name) Set the name of the session.
 * @method static string getId() Get the current session ID.
 * @method static void setId(string $id) Set the session ID.
 * @method static bool isValidId(string $id) Determine if this is a valid session ID.
 * @method static void setExists(bool $value) Set the existence of the session on the handler if applicable.
 * @method static string token() Get the CSRF token value.
 * @method static void regenerateToken() Regenerate the CSRF token value.
 * @method static string|null previousUrl() Get the previous URL from the session.
 * @method static void setPreviousUrl(string $url) Set the "previous" URL in the session.
 * @method static \SessionHandlerInterface getHandler() Get the underlying session handler implementation.
 * @method static bool handlerNeedsRequest() Determine if the session handler needs a request.
 * @method static void setRequestOnHandler(\Illuminate\Http\Request $request) Set the request on the handler instance.
 *
 * @see \Illuminate\Session\SessionManager
 * @see \Illuminate\Session\Store
 */
class Session extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'session';
    }
}
