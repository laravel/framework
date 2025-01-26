<?php

namespace Illuminate\Database\Eloquent\Casts;

/**
 * @template TGet
 * @template TSet
 */
class Attribute
{
    /**
     * The attribute accessor.
     *
     * @var (callable(mixed=, array<string, mixed>=): TGet)|null
     */
    public $get;

    /**
     * The attribute mutator.
     *
     * @var (callable(TSet, array<string, mixed>=): mixed)|null
     */
    public $set;

    /**
     * Indicates if caching is enabled for this attribute.
     *
     * @var bool
     */
    public $withCaching = false;

    /**
     * Indicates if caching of objects is enabled for this attribute.
     *
     * @var bool
     */
    public $withObjectCaching = true;

    /**
     * Create a new attribute accessor / mutator.
     *
     * @param  (callable(mixed=, array<string, mixed>=): TGet)|null  $get
     * @param  (callable(TSet, array<string, mixed>=): mixed)|null  $set
     * @return void
     */
    public function __construct(?callable $get = null, ?callable $set = null)
    {
        $this->get = $get;
        $this->set = $set;
    }

    /**
     * Create a new attribute accessor / mutator.
     *
     * @template TMakeGet
     * @template TMakeSet
     *
     * @param  (callable(mixed=, array<string, mixed>=): TMakeGet)|null  $get
     * @param  (callable(TMakeSet, array<string, mixed>=): mixed)|null  $set
     * @return static<TMakeGet, TMakeSet>
     */
    public static function make(?callable $get = null, ?callable $set = null): static
    {
        return new static($get, $set);
    }

    /**
     * Create a new attribute accessor.
     *
     * @template T
     *
     * @param  callable(mixed=, array<string, mixed>=): T  $get
     * @return static<T, never>
     */
    public static function get(callable $get)
    {
        return new static($get);
    }

    /**
     * Create a new attribute mutator.
     *
     * @template T
     *
     * @param  callable(T, array<string, mixed>=): mixed $set
     * @return static<never, T>
     */
    public static function set(callable $set)
    {
        return new static(null, $set);
    }

    /**
     * Disable object caching for the attribute.
     *
     * @return $this
     */
    public function withoutObjectCaching()
    {
        $this->withObjectCaching = false;

        return $this;
    }

    /**
     * Enable caching for the attribute.
     *
     * @return $this
     */
    public function shouldCache()
    {
        $this->withCaching = true;

        return $this;
    }
}
