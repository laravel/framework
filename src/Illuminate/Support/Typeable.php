<?php

namespace Illuminate\Support;

/**
 * @template TTarget
 */
class Typeable
{
    /**
     * Create a new typeable instance.
     *
     * @param  TTarget  $target
     * @param  string  $typeable
     * @return void
     */
    public function __construct(
        protected mixed $target,
        protected string $typeable)
    {
        //
    }

    /**
     * Retrieve the value for the given typeable.
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
     * @return Stringable|mixed
     */
    public function string(mixed ...$args): mixed
    {
        return str($this->value(...$args));
    }

    /**
     * Retrieve the value as a boolean.
     *
     * @param  mixed  ...$args
     * @return bool
     */
    public function boolean(mixed ...$args): bool
    {
        return filter_var($this->value(...$args), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Retrieve the value as an integer.
     *
     * @param  mixed  ...$args
     * @return int
     */
    public function integer(mixed ...$args): int
    {
        return (int) $this->value(...$args);
    }

    /**
     * Retrieve the value as a float.
     *
     * @param  mixed  ...$args
     * @return float
     */
    public function float(mixed ...$args): float
    {
        return (float) $this->value(...$args);
    }

    /**
     * Retrieve the value as an array.
     *
     * @param  mixed  ...$args
     * @return array
     */
    public function array(mixed ...$args): array
    {
        return (array) $this->value(...$args);
    }

    /**
     * Retrieve the value as an object.
     *
     * @param  mixed  ...$args
     * @return object
     */
    public function object(mixed ...$args): object
    {
        return (object) $this->value(...$args);
    }
}
