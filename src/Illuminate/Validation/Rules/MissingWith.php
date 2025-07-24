<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class MissingWith implements Stringable
{
    public function __construct(protected array $fields) {}

    public function __toString(): string
    {
        return 'missing_with:' . implode(',', $this->fields);
    }
}
