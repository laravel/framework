<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class Uuid implements Stringable
{
    /**
     * @var int<0, 8>|'nil'|'max'|null
     */
    protected int|string|null $version = null;

    /**
     * @param  int<0, 8>|'nil'|'max'  $version
     */
    public function version(int|string $version): static
    {
        $this->version = $version;

        return $this;
    }

    public function __toString(): string
    {
        return 'uuid'.($this->version !== null ? ':'.$this->version : '');
    }
}
