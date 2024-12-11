<?php

namespace Illuminate\View;

use Stringable;

class AppendableAttributeValue implements Stringable
{
    /**
     * Create a new appendable attribute value.
     *
     * @param  mixed  $value
     * @return void
     */
    public function __construct(
        public $value,
    ) {
    }

    /**
     * Get the string value.
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->value;
    }
}
