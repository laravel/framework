<?php

namespace Illuminate\Support;

use Dotenv\Repository\Adapter\PutenvAdapter;
use Dotenv\Repository\RepositoryBuilder;
use Illuminate\Support\EnvProcessors\EnvProcessorInterface;
use PhpOption\Option;

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

            static::$repository = $builder->immutable()->make();
        }

        return static::$repository;
    }

    /**
     * Gets the value of an environment variable and converts it into an array.
     *
     * @param  string  $key
     * @param  array  $default
     * @return array
     */
    public static function array(string $key, array $default = []): array
    {
        return static::getWithProcessor(EnvProcessorFactory::arrayProcessor(), $key, $default);
    }

    /**
     * Gets the value of an environment variable and converts it into a string.
     *
     * @param  string  $key
     * @param  string  $default
     * @return string
     */
    public static function string(string $key, string $default = ''): string
    {
        return static::getWithProcessor(EnvProcessorFactory::stringProcessor(), $key, $default);
    }

    /**
     * Gets the value of an environment variable and converts it into an integer.
     *
     * @param  string  $key
     * @param  int  $default
     * @return int
     */
    public static function integer(string $key, int $default = 0): int
    {
        return static::getWithProcessor(EnvProcessorFactory::integerProcessor(), $key, $default);
    }

    /**
     * Gets the value of an environment variable and converts it into a float.
     *
     * @param  string  $key
     * @param  float  $default
     * @return float
     */
    public static function float(string $key, float $default = 0.0): float
    {
        return static::getWithProcessor(EnvProcessorFactory::floatProcessor(), $key, $default);
    }

    /**
     * Gets the value of an environment variable and converts it into a boolean.
     *
     * @param  string  $key
     * @param  bool  $default
     * @return bool
     */
    public static function boolean(string $key, bool $default = true): bool
    {
        return static::getWithProcessor(EnvProcessorFactory::booleanProcessor(), $key, $default);
    }

    /**
     * Gets the value of an environment variable and trims it.
     *
     * @param  string  $key
     * @param  string|null  $charactersToTrim
     * @param  string  $default
     * @return string
     */
    public static function trim(string $key, ?string $charactersToTrim = null, string $default = ''): string
    {
        return static::getWithProcessor(EnvProcessorFactory::trimProcessor($charactersToTrim), $key, $default);
    }

    /**
     * Returns the content of the file whose path is written in the environment variable.
     *
     * @param  string  $key
     * @param  string  $default
     * @return string
     */
    public static function file(string $key, string $default = ''): string
    {
        return static::getWithProcessor(EnvProcessorFactory::fileProcessor(), $key, $default);
    }

    /**
     * Gets the value of an environment variable and JSON decodes it.
     *
     * @param  string  $key
     * @param  array|null  $default
     * @return array|null
     */
    public static function json(string $key, ?array $default = null): ?array
    {
        return static::getWithProcessor(EnvProcessorFactory::jsonProcessor(), $key, $default);
    }

    /**
     * Gets the value of an environment variable and base64 URL decodes it.
     *
     * @param  string  $key
     * @param  string  $default
     * @return string
     */
    public static function base64(string $key, string $default = ''): string
    {
        return static::getWithProcessor(EnvProcessorFactory::base64Processor(), $key, $default);
    }

    /**
     * Gets the value of an environment variable and runs the given processors on it.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @param  \Illuminate\Support\EnvProcessors\EnvProcessorInterface  ...$processors
     * @return mixed
     */
    public static function chain(string $key, mixed $default = null, EnvProcessorInterface ...$processors): mixed
    {
        return static::getWithProcessor(EnvProcessorFactory::chainProcessor(...$processors), $key, $default);
    }

    /**
     * Gets the value of an environment variable.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        return static::getEnvValue($key)
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
            })
            ->getOrCall(function () use ($default) {
                return value($default);
            });
    }

    /**
     * Gets the value of an environment variable and runs the given processor on it.
     *
     * @param  \Illuminate\Support\EnvProcessors\EnvProcessorInterface  $envProcessor
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public static function getWithProcessor(EnvProcessorInterface $envProcessor, string $key, mixed $default = null): mixed
    {
        $envValue = static::getEnvValue($key);

        if ($envValue->isEmpty()) {
            return value($default);
        }

        return $envProcessor($envValue->get());
    }

    /**
     * Gets the value of an environment variable.
     *
     * @param  string  $key
     * @return \PhpOption\Option
     */
    private static function getEnvValue(string $key): Option
    {
        return Option::fromValue(static::getRepository()->get($key));
    }
}
