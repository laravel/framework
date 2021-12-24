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
     * Create a new attribute accessor / mutator.
     *
     * @param  callable  $get
     * @param  callable  $set
     * @return void
     */
    public function __construct(callable $get = null, callable $set = null)
    {
        $this->get = $get;
        $this->set = $set;
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
