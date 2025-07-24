<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class CurrentPassword implements Stringable
{
    protected ?string $guard = null;

    public function guard(string $guard): static
    {
        $this->guard = $guard;

        return $this;
    }

    public function __toString(): string
    {
        return 'current_password' . ($this->guard ? ":{$this->guard}" : '');
    }
}
