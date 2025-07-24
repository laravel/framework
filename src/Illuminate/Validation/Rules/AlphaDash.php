<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class AlphaDash implements Stringable
{
    protected bool $isAscii = false;

    public function ascii(): static
    {
        $this->isAscii = true;

        return $this;
    }

    public function __toString(): string
    {
        return 'alpha_dash' . ($this->isAscii ? ':ascii' : '');
    }
}
