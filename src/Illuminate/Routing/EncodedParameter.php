<?php

namespace Illuminate\Routing;

use Stringable;

class EncodedParameter implements Stringable
{
    /**
     * Create a new encoded parameter instance.
     *
     * @param  string  $value
     */
    public function __construct(protected string $value)
    {
        //
    }

    /**
     * Get the encoded parameter value.
     *
     * @return string
     */
    public function value()
    {
        return $this->value;
    }

    /**
     * Get the encoded parameter value.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->value;
    }
}
