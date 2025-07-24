<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class RequiredWithAll implements Stringable
{
    public function __construct(protected array $fields)
    {
    }

    public function __toString(): string
    {
        return 'required_with_all:'.implode(',', $this->fields);
    }
}
