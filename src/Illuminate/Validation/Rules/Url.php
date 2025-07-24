<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class Url implements Stringable
{
    protected ?array $protocols = null;

    public function protocols(array $protocols): static
    {
        $this->protocols = $protocols;

        return $this;
    }

    public function __toString(): string
    {
        return 'url'.($this->protocols ? ':'.implode(',', $this->protocols) : '');
    }
}
