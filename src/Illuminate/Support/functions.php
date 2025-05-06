<?php

namespace Illuminate\Support;

use Illuminate\Support\Defer\DeferredCallback;
use Illuminate\Support\Defer\DeferredCallbackCollection;
use InvalidArgumentException;
use Symfony\Component\Process\PhpExecutableFinder;

if (! function_exists('Illuminate\Support\defer')) {
    /**
     * Defer execution of the given callback.
     *
     * @param  callable|null  $callback
     * @param  string|null  $name
     * @param  bool  $always
     * @return \Illuminate\Support\Defer\DeferredCallback
     */
    function defer(?callable $callback = null, ?string $name = null, bool $always = false)
    {
        if ($callback === null) {
            return app(DeferredCallbackCollection::class);
        }

        return tap(
            new DeferredCallback($callback, $name, $always),
            fn ($deferred) => app(DeferredCallbackCollection::class)[] = $deferred
        );
    }
}

if (! function_exists('Illuminate\Support\php_binary')) {
    /**
     * Determine the PHP Binary.
     *
     * @return string
     */
    function php_binary()
    {
        return (new PhpExecutableFinder)->find(false) ?: 'php';
    }
}

if (! function_exists('Illuminate\Support\artisan_binary')) {
    /**
     * Determine the proper Artisan executable.
     *
     * @return string
     */
    function artisan_binary()
    {
        return defined('ARTISAN_BINARY') ? ARTISAN_BINARY : 'artisan';
    }
}

if (! function_exists('Illuminate\Support\check_type')) {
    /**
     * Validate that a given value matches the expected type.
     *
     * @param  mixed  $value
     * @param  'string'|'int'|'integer'|'long'|'bool'|'boolean'|'float'|'double'|'real'|'array'  $type
     * @param  string  $key
     * @param  string  $group
     * @return mixed
     *
     * @throws InvalidArgumentException If the value does not match the expected type.
     */
    function check_type(mixed $value, string $type, string $key, string $group = 'variable'): mixed
    {
        $isValid = match ($type) {
            'string' => is_string($value),
            'int', 'integer', 'long' => filter_var($value, FILTER_VALIDATE_INT) !== false,
            'bool', 'boolean' => is_bool($value),
            'float', 'double', 'real' => filter_var($value, FILTER_VALIDATE_FLOAT) !== false,
            'array' => is_array($value),
            default => throw new InvalidArgumentException('Type "'.$type.'" is not supported. Use one of: string, int, integer, long, bool, boolean, float, double, real, array')
        };

        throw_unless(
            $isValid,
            InvalidArgumentException::class,
            sprintf('%s value for key [%s] must be a %s, %s given.',
                $group,
                $key,
                $type,
                is_object($value) ? get_class($value) : gettype($value)
            ),
        );

        return $value;
    }
}
