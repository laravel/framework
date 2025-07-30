<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class RequiredWithoutAll implements Stringable
{
    public function __construct(protected array $fields)
    {
    }

    public function __toString(): string
    {
        return 'required_without_all:'.implode(',', $this->fields);
    }
}
