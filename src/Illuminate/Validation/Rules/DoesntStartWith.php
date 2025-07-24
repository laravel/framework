<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class DoesntStartWith implements Stringable
{
    public function __construct(protected array $values)
    {
    }

    public function __toString(): string
    {
        return 'doesnt_start_with:'.implode(',', $this->values);
    }
}
