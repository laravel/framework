<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class Required implements Stringable
{
    /**
     * Convert the rule to a validation string.
     *
     * @return string
     */
    public function __toString()
    {
        return 'required';
    }
}
