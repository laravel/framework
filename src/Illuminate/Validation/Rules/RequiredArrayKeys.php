<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Validation\Rules\Concerns\FormatsParameters;
use Stringable;

class RequiredArrayKeys implements Stringable
{
    use FormatsParameters;

    protected array $keys;

    /**
     * Create a new required_array_keys rule instance.
     */
    public function __construct(array|string $keys)
    {
        $this->keys = is_array($keys) ? $keys : func_get_args();
    }

    /**
     * Convert the rule to a validation string.
     */
    public function __toString(): string
    {
        return 'required_array_keys:'.$this->formatParameters($this->keys);
    }
}
