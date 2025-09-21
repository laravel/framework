<?php

namespace Illuminate\Database\Eloquent\Casts;

use Illuminate\Contracts\Database\Eloquent\Castable;
use InvalidArgumentException;
use Stringable;

/**
 * @template TBaseClass of \Illuminate\Database\Eloquent\Casts\ArrayObject|\Illuminate\Support\Collection|\Illuminate\Support\Fluent
 */
class ToIterable implements Stringable, Castable
{
    /**
     * Create a new To Enumerable instance.
     *
     * @param  class-string<TBaseClass>  $class
     * @param  bool|null  $withoutCaching
     * @param  bool|null  $encrypt
     * @param  class-string<TBaseClass>|null  $using
     * @param  callable-string|array{class-string, string}|null  $map
     */
    public function __construct(
        protected $class,
        protected $withoutCaching = null,
        protected $encrypt = null,
        protected $using = null,
        protected $map = null,
    ) {
        //
    }

    /**
     * Disables object caching for the cast.
     *
     * @return $this
     */
    public function withoutCaching()
    {
        $this->withoutCaching = true;

        return $this;
    }

    /**
     * Encrypts the database values.
     *
     * @return $this
     */
    public function encrypted()
    {
        $this->encrypt = true;

        return $this;
    }

    /**
     * Uses a different Collection or Array Object class to build the enumerable object.
     *
     * @param  class-string<TBaseClass>  $class
     * @return $this
     */
    public function using($class)
    {
        $this->using = $class;

        return $this;
    }

    /**
     * Maps the items into the given class.
     *
     * @param  class-string  $class
     * @return $this
     */
    public function mappedInto(string $class)
    {
        $this->map = $class;

        return $this;
    }

    /**
     * Maps the items into the given class.
     *
     * @param  class-string  $class
     * @return $this
     */
    public function of(string $class)
    {
        return $this->mappedInto($class);
    }

    /**
     * Maps the items into the given backed enum.
     *
     * @param  class-string<\BackedEnum>  $enum
     * @return $this
     */
    public function enum($enum)
    {
        return $this->mappedInto($enum);
    }

    /**
     * Maps the items using the given string or array callable, or "method@class" notation.
     *
     * @param  \Closure|string|array  $callable
     * @return $this
     */
    public function mapped($callable)
    {
        $this->map = $this->decomposeCallable($callable);

        return $this;
    }

    /**
     * Maps the items using the `fromArray()` static method of the given class.
     *
     * @param  class-string  $class
     * @return $this
     */
    public function mappedFromArray($class)
    {
        return $this->mapped([$class, 'fromArray']);
    }

    /**
     * Decompose the callable into a string.
     *
     * @param  callable  $callable
     * @return string
     */
    protected function decomposeCallable($callable): string
    {
        if (is_string($callable)) {
            return $callable;
        }

        if (is_array($callable) && is_callable($callable)) {
            return $callable[0].'@'.$callable[1];
        }

        throw new InvalidArgumentException(
            'The callable must be an array, string, or a string in "method@class" notation.'
        );
    }

    /**
     * Create a string representation of the cast.
     *
     * @return string
     */
    public function __toString(): string
    {
        return static::class.':'.implode(',', [
            $this->class, $this->withoutCaching, $this->encrypt, $this->using, $this->map,
        ]);
    }

    /**
     * @inheritDoc
     *
     * @param  array{class-string<TBaseClass>, string|null, string|null, class-string<TBaseClass>|null, string|null}  $arguments
     */
    public static function castUsing(array $arguments)
    {
        // The Array Object CastAttribute has a custom serialization method.
        return $arguments[0] === ArrayObject::class
            ? new ArrayObjectCast($arguments)
            : new IterableCast($arguments);
    }
}
