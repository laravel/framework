<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class RequiredWith implements Stringable
{
    protected array $fields;

    public function __construct(array|string $fields)
    {
        $this->fields = is_array($fields) ? $fields : func_get_args();
    }

    public function __toString(): string
    {
        return 'required_with:'.implode(',', $this->fields);
    }
}
