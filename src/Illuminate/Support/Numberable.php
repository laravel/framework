<?php

namespace Illuminate\Support;

use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Support\Traits\Tappable;

class Numberable
{
    use Conditionable, Macroable, Tappable;

    /**
     * The underlying numeric value.
     *
     * @var int|float
     */
    protected $value;

    /**
     * Create a new instance of the class.
     *
     * @param  int|float  $value
     * @return void
     */
    public function __construct($value = 0)
    {
        $this->value = $value;
    }

    /**
     * Get the raw numeric value.
     *
     * @return int|float
     */
    public function value()
    {
        return $this->value;
    }

    /**
     * Proxy dynamic properties onto methods.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->{$key}();
    }

    /**
     * Get the raw string value.
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->value;
    }
}
