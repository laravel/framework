<?php

namespace Illuminate\Validation\Rules;

use InvalidArgumentException;
use Stringable;

class Decimal implements Stringable
{
    public function __construct(
        protected int $minPlaces,
        protected ?int $maxPlaces,
    )
    {
        if (! is_null($this->maxPlaces) && $this->minPlaces > $this->maxPlaces) {
            throw new InvalidArgumentException('The min places value cannot be greater than the max places value.');
        }
    }

    public function __toString(): string
    {
        if (is_null($this->maxPlaces)) {
            return "decimal:{$this->minPlaces}";
        }

        return "decimal:{$this->minPlaces},{$this->maxPlaces}";
    }
}
