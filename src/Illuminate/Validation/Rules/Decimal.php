<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class Decimal implements Stringable
{
    public function __construct(
        protected int $minPlaces,
        protected ?int $maxPlaces,
    ) {
    }

    public function __toString(): string
    {
        if (is_null($this->maxPlaces)) {
            return "decimal:{$this->minPlaces}";
        }

        return "decimal:{$this->minPlaces},{$this->maxPlaces}";
    }
}
