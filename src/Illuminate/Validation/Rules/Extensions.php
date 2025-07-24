<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class Extensions implements Stringable
{
    public function __construct(protected array $extensions) {}

    public function __toString(): string
    {
        return 'extensions:' . implode(',', $this->extensions);
    }
}
