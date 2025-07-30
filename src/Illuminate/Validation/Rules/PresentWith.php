<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class PresentWith implements Stringable
{
    public function __construct(protected array $fields)
    {
    }

    public function __toString(): string
    {
        return 'present_with:'.implode(',', $this->fields);
    }
}
