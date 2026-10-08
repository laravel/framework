<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class Distinct implements Stringable
{
    protected array $options = [];

    /**
     * Compare values using strict comparisons.
     */
    public function strict(): static
    {
        $this->options[] = 'strict';

        return $this;
    }

    /**
     * Ignore case when comparing string values.
     */
    public function ignoreCase(): static
    {
        $this->options[] = 'ignore_case';

        return $this;
    }

    /**
     * Convert the rule to a validation string.
     */
    public function __toString(): string
    {
        if ($this->options) {
            return 'distinct:'.implode(',', array_unique($this->options));
        }

        return 'distinct';
    }
}
