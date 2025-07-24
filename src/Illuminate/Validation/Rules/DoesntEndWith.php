<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class DoesntEndWith implements Stringable
{
    public function __construct(protected array $values)
    {
    }

    public function __toString(): string
    {
        return 'doesnt_end_with:'.implode(',', $this->values);
    }
}
