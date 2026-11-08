<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Support\Arr;
use Stringable;

class ListRule implements Stringable
{
    /**
     * The additional constraints for the list rule.
     */
    protected array $constraints = [];

    /**
     * The field under validation must have between the given min and max number of items (inclusive).
     *
     * @param  non-negative-int  $min
     * @param  non-negative-int  $max
     * @return $this
     */
    public function between(int $min, int $max): static
    {
        return $this->addRule('between:'.$min.','.$max);
    }

    /**
     * The field under validation must have exactly the given number of items.
     *
     * @param  int  $value
     * @return $this
     */
    public function exactly(int $value): static
    {
        return $this->addRule('size:'.$value);
    }

    /**
     * The field under validation must not have more than the given number of items.
     *
     * @param  non-negative-int  $value
     * @return $this
     */
    public function max(int $value): static
    {
        return $this->addRule('max:'.$value);
    }

    /**
     * The field under validation must have at least the given number of items.
     *
     * @param  int  $value
     * @return $this
     */
    public function min(int $value): static
    {
        return $this->addRule('min:'.$value);
    }

    /**
     * Convert the rule to a validation string.
     *
     * @return string
     */
    public function __toString()
    {
        return implode('|', array_unique(['list', ...$this->constraints]));
    }

    /**
     * Add custom rules to the validation rules array.
     */
    protected function addRule(array|string $rules): static
    {
        $this->constraints = array_merge($this->constraints, Arr::wrap($rules));

        return $this;
    }
}
