<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class Prohibits implements Stringable
{
    protected array $fields;

    public function __construct(array|string $fields)
    {
        $this->fields = is_array($fields) ? $fields : func_get_args();
    }

    public function __toString(): string
    {
        return 'prohibits:'.implode(',', $this->fields);
    }
}
