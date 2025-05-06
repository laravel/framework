<?php

namespace Illuminate\Support;

use Closure;
use Dotenv\Repository\Adapter\PutenvAdapter;
use Dotenv\Repository\RepositoryBuilder;
use InvalidArgumentException;
use PhpOption\Option;
use RuntimeException;

class Env
{
    /**
     * Indicates if the putenv adapter is enabled.
     *
     * @var bool
     */
    protected static $putenv = true;

    /**
     * The environment repository instance.
     *
     * @var \Dotenv\Repository\RepositoryInterface|null
     */
    protected static $repository;

    /**
     * The list of custom adapters for loading environment variables.
     *
     * @var array<Closure>
     */
    protected static $customAdapters = [];

    /**
     * Enable the putenv adapter.
     *
     * @return void
     */
    public static function enablePutenv()
    {
        static::$putenv = true;
        static::$repository = null;
    }

    /**
     * Disable the putenv adapter.
     *
     * @return void
     */
    public static function disablePutenv()
    {
        static::$putenv = false;
        static::$repository = null;
    }

    /**
     * Register a custom adapter creator Closure.
     */
    public static function extend(Closure $callback, ?string $name = null): void
    {
        if (! is_null($name)) {
            static::$customAdapters[$name] = $callback;
        } else {
            static::$customAdapters[] = $callback;
        }
    }

    /**
     * Get the environment repository instance.
     *
     * @return \Dotenv\Repository\RepositoryInterface
     */
    public static function getRepository()
    {
        if (static::$repository === null) {
            $builder = RepositoryBuilder::createWithDefaultAdapters();

            if (static::$putenv) {
                $builder = $builder->addAdapter(PutenvAdapter::class);
            }

            foreach (static::$customAdapters as $adapter) {
                $builder = $builder->addAdapter($adapter());
            }

            static::$repository = $builder->immutable()->make();
        }

        return static::$repository;
    }

    /**
     * Get the value of an environment variable.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        return self::getOption($key)->getOrCall(fn () => value($default));
    }

    /**
     * Get the value of a required environment variable.
     *
     * @param  string  $key
     * @return mixed
     *
     * @throws \RuntimeException
     */
    public static function getOrFail($key)
    {
        return self::getOption($key)->getOrThrow(new RuntimeException("Environment variable [$key] has no value."));
    }

    /**
     * Get the specified string environment value.
     *
     * @param  string  $key
     * @param  (Closure():(string|null))|string|null  $default
     * @return string
     *
     * @throws InvalidArgumentException
     */
    public static function string(string $key, $default = null): string
    {
        $value = Env::get($key, $default);

        if (! is_string($value)) {
            throw new InvalidArgumentException(
                sprintf('Environment value for key [%s] must be a string, %s given.', $key, gettype($value))
            );
        }

        return $value;
    }

    /**
     * Get the specified integer environment value.
     *
     * @param  string  $key
     * @param  (Closure():(int|null))|int|null  $default
     * @return int
     *
     * @throws InvalidArgumentException
     */
    public static function integer(string $key, $default = null): int
    {
        $value = Env::get($key, $default);

        if (filter_var($value, FILTER_VALIDATE_INT) === false) {
            throw new InvalidArgumentException(
                sprintf('Environment value for key [%s] must be an integer, %s given.', $key, gettype($value))
            );
        }

        return $value;
    }

    /**
     * Get the specified float environment value.
     *
     * @param  string  $key
     * @param  (Closure():(float|null))|float|null  $default
     * @return float
     *
     * @throws InvalidArgumentException
     */
    public static function float(string $key, $default = null): float
    {
        $value = Env::get($key, $default);

        if (filter_var($value, FILTER_VALIDATE_FLOAT) === false) {
            throw new InvalidArgumentException(
                sprintf('Environment value for key [%s] must be a float, %s given.', $key, gettype($value))
            );
        }

        return $value;
    }

    /**
     * Get the specified boolean environment value.
     *
     * @param  string  $key
     * @param  (Closure():(bool|null))|bool|null  $default
     * @return bool
     *
     * @throws InvalidArgumentException
     */
    public static function boolean(string $key, $default = null): bool
    {
        $value = Env::get($key, $default);

        if (! is_bool($value)) {
            throw new InvalidArgumentException(
                sprintf('Environment value for key [%s] must be a boolean, %s given.', $key, gettype($value))
            );
        }

        return $value;
    }

    /**
     * Get the specified array environment value.
     *
     * @param  string  $key
     * @param  (Closure():(array|null))|array|null  $default
     * @return array
     *
     * @throws InvalidArgumentException
     */
    public static function array(string $key, $default = null): array
    {
        $value = Env::get($key, $default);

        if ($value === null) {
            return [];
        }

        if (is_string($value)) {
            return array_map('trim', explode(',', $value));
        }

        if (! is_array($value)) {
            throw new InvalidArgumentException(
                sprintf('Environment value for key [%s] must be an array or comma-separated string, %s given.', $key, gettype($value))
            );
        }

        return $value;
    }

    /**
     * Get the possible option for this environment variable.
     *
     * @param  string  $key
     * @return \PhpOption\Option|\PhpOption\Some
     */
    protected static function getOption($key)
    {
        return Option::fromValue(static::getRepository()->get($key))
            ->map(function ($value) {
                switch (strtolower($value)) {
                    case 'true':
                    case '(true)':
                        return true;
                    case 'false':
                    case '(false)':
                        return false;
                    case 'empty':
                    case '(empty)':
                        return '';
                    case 'null':
                    case '(null)':
                        return;
                }

                if (preg_match('/\A([\'"])(.*)\1\z/', $value, $matches)) {
                    return $matches[2];
                }

                return $value;
            });
    }
}
