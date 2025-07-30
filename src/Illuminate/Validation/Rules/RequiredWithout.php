<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class RequiredWithout implements Stringable
{
    public function __construct(protected array $fields)
    {
    }

    public function __toString(): string
    {
        return 'required_without:'.implode(',', $this->fields);
    }
}
