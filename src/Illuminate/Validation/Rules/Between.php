<?php

namespace Illuminate\Validation\Rules;

use InvalidArgumentException;
use Stringable;

class Between implements Stringable
{
    protected int|float $min;
    protected int|float $max;

    /**
     * Create a new rule instance.
     *
     * @param int|float $min
     * @param int|float $max
     * @throws InvalidArgumentException
     */
    public function __construct(int|float $min, int|float $max)
    {
        if ($min > $max) {
            throw new InvalidArgumentException('The min value cannot be greater than the max value.');
        }

        $this->min = $min;
        $this->max = $max;
    }

    public function __toString(): string
    {
        return "between:{$this->min},{$this->max}";
    }
}
