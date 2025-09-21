<?php

namespace Illuminate\Database\Eloquent\Casts;

use Illuminate\Contracts\Database\Eloquent\Castable;
use Stringable;

/**
 * @template TBaseClass of \Illuminate\Support\HtmlString|\Illuminate\Support\Stringable|\Illuminate\Support\Uri
 */
class ToString implements Stringable, Castable
{
    /**
     * Create a new To Enumerable instance.
     *
     * @param  class-string<TBaseClass>  $class
     * @param  bool|null  $withoutCaching
     * @param  bool|null  $encrypt
     * @param  class-string<TBaseClass>|null  $using
     */
    public function __construct(
        protected $class,
        protected $withoutCaching = null,
        protected $encrypt = null,
        protected $using = null,
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
     * Create a string representation of the cast.
     *
     * @return string
     */
    public function __toString(): string
    {
        return static::class.':'.implode(',', [$this->class, $this->withoutCaching, $this->encrypt, $this->using]);
    }

    /**
     * @inheritDoc
     *
     * @param  array{class-string<TBaseClass>, string|null, string|null, class-string<TBaseClass>|null}  $arguments
     * @return \Illuminate\Database\Eloquent\Casts\StringableCast<TBaseClass>
     */
    public static function castUsing(array $arguments)
    {
        return new StringableCast($arguments);
    }
}
