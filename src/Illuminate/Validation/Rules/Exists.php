<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Support\Traits\Conditional;

class Exists
{
    use DatabaseRule;
    use Conditional;

    /**
     * Convert the rule to a validation string.
     *
     * @return string
     */
    public function __toString()
    {
        return rtrim(sprintf('exists:%s,%s,%s',
            $this->table,
            $this->column,
            $this->formatWheres()
        ), ',');
    }
}
