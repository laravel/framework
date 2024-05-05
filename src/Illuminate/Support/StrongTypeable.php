<?php

namespace Illuminate\Support;

use Closure;
use InvalidArgumentException;

/**
 * @template TTarget
 */
class StrongTypeable
{
    /**
     * Create a new strong typeable instance.
     *
     * @param  TTarget  $target
     * @param  string  $typeable
     * @return void
     */
    public function __construct(protected mixed $target, protected string $typeable)
    {
        //
    }

    /**
     * Retrieve the value for the given strong typeable.
     *
     * @param  mixed  ...$args
     * @return mixed
     */
    protected function value(mixed ...$args): mixed
    {
        return $this->target->{$this->typeable}(...$args);
    }

    /**
     * Retrieve the value as a string.
     *
     * @param  mixed  ...$args
     * @return string
     */
    public function string(mixed ...$args): string
    {
        if (! is_string($value = $this->value(...$args))) {
            throw new InvalidArgumentException(
                sprintf('Typed property [%s] must be a string, %s given.', $args[0], gettype($value))
            );
        }

        return $value;
    }

    /**
     * Retrieve the value as a boolean.
     *
     * @param  mixed  ...$args
     * @return bool
     */
    public function boolean(mixed ...$args): bool
    {
        if (! is_bool($value = $this->value(...$args))) {
            throw new InvalidArgumentException(
                sprintf('Typed property [%s] must be a boolean, %s given.', $args[0], gettype($value))
            );
        }

        return $value;
    }

    /**
     * Retrieve the value as an integer.
     *
     * @param  mixed  ...$args
     * @return int
     */
    public function integer(mixed ...$args): int
    {
        if (! is_int($value = $this->value(...$args))) {
            throw new InvalidArgumentException(
                sprintf('Typed property [%s] must be an integer, %s given.', $args[0], gettype($value))
            );
        }

        return $value;
    }

    /**
     * Retrieve the value as a float.
     *
     * @param  mixed  ...$args
     * @return float
     */
    public function float(mixed ...$args): float
    {
        if (! is_float($value = $this->value(...$args))) {
            throw new InvalidArgumentException(
                sprintf('Typed property [%s] must be a float, %s given.', $args[0], gettype($value))
            );
        }

        return $value;
    }

    /**
     * Retrieve the value as an array.
     *
     * @param  mixed  ...$args
     * @return array
     */
    public function array(mixed ...$args): array
    {
        if (! is_array($value = $this->value(...$args))) {
            throw new InvalidArgumentException(
                sprintf('Typed property [%s] must be an array, %s given.', $args[0], gettype($value))
            );
        }

        return $value;
    }

    /**
     * Retrieve the value as an object.
     *
     * @param  mixed  ...$args
     * @return object
     */
    public function object(mixed ...$args): object
    {
        if (!is_object($value = $this->value(...$args))) {
            throw new InvalidArgumentException(
                sprintf('Typed property [%s] must be an object, %s given.', $args[0], gettype($value))
            );
        }

        return $value;
    }
}
