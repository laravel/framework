<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Contracts\Validation\ParameterizedRule;
use Illuminate\Validation\Rules\Concerns\FormatsParameters;

class Distinct implements ParameterizedRule
{
    use FormatsParameters;

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
     * Get the rule name followed by its parameters.
     */
    public function toArray(): array
    {
        return ['distinct', ...array_values(array_unique($this->options))];
    }
}
