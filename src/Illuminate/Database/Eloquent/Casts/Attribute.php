<?php

namespace Illuminate\Database\Eloquent\Casts;

class Attribute
{
    /**
     * The attribute accessor.
     *
     * @var callable
     */
    public $get;

    /**
     * The attribute mutator.
     *
     * @var callable
     */
    public $set;

    /**
     * Whether caching of objects should be disabled for this attribute.
     *
     * @var bool
     */
    public $disableObjectCaching = false;

    /**
     * Create a new attribute accessor / mutator.
     *
     * @param  callable|null  $get
     * @param  callable|null  $set
     * @return void
     */
    public function __construct(callable $get = null, callable $set = null)
    {
        $this->get = $get;
        $this->set = $set;
    }

    /**
     * @return static
     */
    public function disableObjectCaching()
    {
        $this->disableObjectCaching = true;

        return $this;
    }

    /**
     * Create a new attribute accessor.
     *
     * @param  callable  $get
     * @return static
     */
    public static function get(callable $get)
    {
        return new static($get);
    }

    /**
     * Create a new attribute accessor with object caching disabled.
     *
     * @param  callable  $get
     * @return static
     */
    public static function getWithoutCaching(callable $get)
    {
        return (new static($get))->disableObjectCaching();
    }

    /**
     * Create a new attribute mutator.
     *
     * @param  callable  $set
     * @return static
     */
    public static function set(callable $set)
    {
        return new static(null, $set);
    }
}
