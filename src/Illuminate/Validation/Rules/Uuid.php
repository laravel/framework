<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class Uuid implements Stringable
{
    protected int|null $version = null;

    public function version(int $version): static
    {
        $this->version = $version;

        return $this;
    }

    public function __toString(): string
    {
        return 'uuid' . ($this->version ? ':' . $this->version : '');
    }
}
