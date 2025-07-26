<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class MissingWithAll implements Stringable
{
    public function __construct(protected array $fields)
    {
    }

    public function __toString(): string
    {
        return 'missing_with_all:'.implode(',', $this->fields);
    }
}
