<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Validation\Rules\Concerns\FormatsParameters;
use Stringable;

class RequiredWithAll implements Stringable
{
    use FormatsParameters;

    protected array $fields;

    /**
     * Create a new required_with_all rule instance.
     */
    public function __construct(array|string $fields)
    {
        $this->fields = is_array($fields) ? $fields : func_get_args();
    }

    /**
     * Convert the rule to a validation string.
     */
    public function __toString(): string
    {
        return 'required_with_all:'.$this->formatParameters($this->fields);
    }
}
