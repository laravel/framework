<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Support\Traits\Conditionable;
use Stringable;

class NotIn implements Stringable
{
    use ArrayableRule, Conditionable;

    /**
     * Convert the rule to a validation string.
     *
     * @return string
     */
    public function __toString()
    {
        return 'not_in:'.implode(',', $this->formatValues());
    }
}
