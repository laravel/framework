<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class Timezone implements Stringable
{
    /**
     * Create a new timezone rule instance.
     */
    public function __construct(protected ?array $arguments = null)
    {
    }

    /**
     * Convert the rule to a validation string.
     */
    public function __toString(): string
    {
        return 'timezone'.($this->arguments ? ':'.implode(',', $this->arguments) : '');
    }
}
