<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class Distinct implements Stringable
{
    protected ?string $option = null;

    public function strict(): static
    {
        $this->option = 'strict';

        return $this;
    }

    public function ignoreCase(): static
    {
        $this->option = 'ignore_case';

        return $this;
    }

    public function __toString(): string
    {
        if ($this->option) {
            return "distinct:{$this->option}";
        }

        return 'distinct';
    }
}
