<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class MimeTypes implements Stringable
{
    public function __construct(protected array $types) {}

    public function __toString(): string
    {
        return 'mimetypes:' . implode(',', $this->types);
    }
}
