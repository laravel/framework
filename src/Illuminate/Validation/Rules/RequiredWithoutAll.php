<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Contracts\Validation\ParameterizedRule;
use Illuminate\Validation\Rules\Concerns\FormatsParameters;

class RequiredWithoutAll implements ParameterizedRule
{
    use FormatsParameters;

    protected array $fields;

    /**
     * Create a new required_without_all rule instance.
     */
    public function __construct(array|string $fields)
    {
        $this->fields = is_array($fields) ? $fields : func_get_args();
    }

    /**
     * Get the rule name followed by its parameters.
     */
    public function toArray(): array
    {
        return ['required_without_all', ...array_values($this->fields)];
    }
}
