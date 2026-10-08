<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Validation\Rules\Concerns\FormatsParameters;
use Stringable;

class CurrentPassword implements Stringable
{
    use FormatsParameters;

    protected ?string $guard = null;

    /**
     * Specify the authentication guard.
     */
    public function guard(string $guard): static
    {
        $this->guard = $guard;

        return $this;
    }

    /**
     * Convert the rule to a validation string.
     */
    public function __toString(): string
    {
        return 'current_password'.($this->guard !== null ? ':'.$this->formatParameters([$this->guard]) : '');
    }
}
