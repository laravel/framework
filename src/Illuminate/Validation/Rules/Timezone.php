<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class Timezone implements Stringable
{
    public function __construct(protected ?array $arguments = null) {}

    public function __toString(): string
    {
        return 'timezone' . ($this->arguments ? ':' . implode(',', $this->arguments) : '');
    }
}
