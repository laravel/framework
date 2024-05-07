<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Support\Traits\Conditionable;
use Stringable;

class In implements Stringable
{
    use ArrayableRule, Conditionable;

    /**
     * Convert the rule to a validation string.
     *
     * @return string
     */
    public function __toString()
    {
        return 'in:'.implode(',', $this->formatValues());
    }
}
