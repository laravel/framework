<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class Alpha implements Stringable
{
    protected bool $isAscii = false;

    public function ascii(): static
    {
        $this->isAscii = true;

        return $this;
    }

    public function __toString(): string
    {
        return 'alpha'.($this->isAscii ? ':ascii' : '');
    }
}
