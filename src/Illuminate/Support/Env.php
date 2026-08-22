<?php

namespace Illuminate\Support;

use Closure;
use Dotenv\Repository\Adapter\PutenvAdapter;
use Dotenv\Repository\RepositoryBuilder;
use Illuminate\Filesystem\Filesystem;
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
     * @throws \InvalidArgumentException
     */
    public function string(string $key, $default = null): string
    {
        $value = static::get($key, $default);

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
     * @throws \InvalidArgumentException
     */
    public function integer(string $key, $default = null): int
    {
        $value = static::get($key, $default);

        if (is_int($value)) {
            return $value;
        }

        if (filter_var($value, FILTER_VALIDATE_INT) !== false) {
            return (int) $value;
        }

        throw new InvalidArgumentException(
            sprintf('Environment value for key [%s] must be an integer, %s given.', $key, gettype($value))
        );
    }

    /**
     * Get the specified float environment value.
     *
     * @param  string  $key
     * @param  (Closure():(float|null))|float|null  $default
     * @return float
     *
     * @throws \InvalidArgumentException
     */
    public function float(string $key, $default = null): float
    {
        $value = static::get($key, $default);

        if (is_float($value)) {
            return $value;
        }

        if (filter_var($value, FILTER_VALIDATE_FLOAT) !== false) {
            return (float) $value;
        }

        throw new InvalidArgumentException(
            sprintf('Environment value for key [%s] must be a float, %s given.', $key, gettype($value))
        );
    }

    /**
     * Get the specified boolean environment value.
     *
     * @param  string  $key
     * @param  (Closure():(bool|null))|bool|null  $default
     * @return bool
     *
     * @throws \InvalidArgumentException
     */
    public function boolean(string $key, $default = null): bool
    {
        $value = static::get($key, $default);

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
     * @param  (Closure():(array<array-key, mixed>|null))|array<array-key, mixed>|null  $default
     * @return array<array-key, mixed>
     *
     * @throws \InvalidArgumentException
     */
    public function array(string $key, $default = null): array
    {
        $value = static::get($key, $default);

        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            return trim($value) === '' ? [] : array_map('trim', explode(',', $value));
        }

        throw new InvalidArgumentException(
            sprintf('Environment value for key [%s] must be an array, %s given.', $key, gettype($value))
        );
    }

    /**
     * Get the specified array environment value as a collection.
     *
     * @param  string  $key
     * @param  (Closure():(array<array-key, mixed>|null))|array<array-key, mixed>|null  $default
     * @return Collection<array-key, mixed>
     */
    public function collection(string $key, $default = null): Collection
    {
        return new Collection($this->array($key, $default));
    }

    /**
     * Write an array of key-value pairs to the environment file.
     *
     * @param  array<string, mixed>  $variables
     * @param  string  $pathToFile
     * @param  bool  $overwrite
     * @return void
     *
     * @throws \RuntimeException
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public static function writeVariables(array $variables, string $pathToFile, bool $overwrite = false): void
    {
        $filesystem = new Filesystem;

        if ($filesystem->missing($pathToFile)) {
            throw new RuntimeException("The file [{$pathToFile}] does not exist.");
        }

        $lines = explode(PHP_EOL, $filesystem->get($pathToFile));

        foreach ($variables as $key => $value) {
            $lines = self::addVariableToEnvContents($key, $value, $lines, $overwrite);
        }

        $filesystem->put($pathToFile, implode(PHP_EOL, $lines));
    }

    /**
     * Write a single key-value pair to the environment file.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  string  $pathToFile
     * @param  bool  $overwrite
     * @return void
     *
     * @throws \RuntimeException
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public static function writeVariable(string $key, mixed $value, string $pathToFile, bool $overwrite = false): void
    {
        $filesystem = new Filesystem;

        if ($filesystem->missing($pathToFile)) {
            throw new RuntimeException("The file [{$pathToFile}] does not exist.");
        }

        $envContent = $filesystem->get($pathToFile);

        $lines = explode(PHP_EOL, $envContent);
        $lines = self::addVariableToEnvContents($key, $value, $lines, $overwrite);

        $filesystem->put($pathToFile, implode(PHP_EOL, $lines));
    }

    /**
     * Add a variable to the environment file contents.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  array<int, string>  $envLines
     * @param  bool  $overwrite
     * @return array<int, string>
     */
    protected static function addVariableToEnvContents(string $key, mixed $value, array $envLines, bool $overwrite): array
    {
        $prefix = explode('_', $key)[0].'_';
        $lastPrefixIndex = -1;

        $shouldQuote = preg_match('/^[a-zA-Z0-9]+$/', $value) === 0;

        $lineToAddVariations = [
            $key.'='.(is_string($value) ? self::prepareQuotedValue($value) : $value),
            $key.'='.$value,
        ];

        $lineToAdd = $shouldQuote ? $lineToAddVariations[0] : $lineToAddVariations[1];

        if ($value === '') {
            $lineToAdd = $key.'=';
        }

        foreach ($envLines as $index => $line) {
            if (str_starts_with($line, $prefix)) {
                $lastPrefixIndex = $index;
            }

            if (in_array($line, $lineToAddVariations)) {
                // This exact line already exists, so we don't need to add it again.
                return $envLines;
            }

            if ($line === $key.'=') {
                // If the value is empty, we can replace it with the new value.
                $envLines[$index] = $lineToAdd;

                return $envLines;
            }

            if (str_starts_with($line, $key.'=')) {
                if (! $overwrite) {
                    return $envLines;
                }

                $envLines[$index] = $lineToAdd;

                return $envLines;
            }
        }

        if ($lastPrefixIndex === -1) {
            if (count($envLines) && $envLines[count($envLines) - 1] !== '') {
                $envLines[] = '';
            }

            return array_merge($envLines, [$lineToAdd]);
        }

        return array_merge(
            array_slice($envLines, 0, $lastPrefixIndex + 1),
            [$lineToAdd],
            array_slice($envLines, $lastPrefixIndex + 1)
        );
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

    /**
     * Wrap a string in quotes, choosing double or single quotes.
     *
     * @param  string  $input
     * @return string
     */
    protected static function prepareQuotedValue(string $input)
    {
        return str_contains($input, '"')
            ? "'".self::addSlashesExceptFor($input, ['"'])."'"
            : '"'.self::addSlashesExceptFor($input, ["'"]).'"';
    }

    /**
     * Escape a string using addslashes, excluding the specified characters from being escaped.
     *
     * @param  string  $value
     * @param  array<string>  $except
     * @return string
     */
    protected static function addSlashesExceptFor(string $value, array $except = [])
    {
        $escaped = addslashes($value);

        foreach ($except as $character) {
            $escaped = str_replace('\\'.$character, $character, $escaped);
        }

        return $escaped;
    }
}
