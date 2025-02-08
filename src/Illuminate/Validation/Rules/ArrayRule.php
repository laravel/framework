<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Illuminate\Support\Traits\Conditionable;
use Stringable;

use function Illuminate\Support\enum_value;

class ArrayRule implements Stringable
{
    use Conditionable;

    /**
     * The constraints for the number rule.
     */
    protected array $constraints = [];

    /**
     * Create a new array rule instance.
     *
     * @param  array|null  $keys
     * @return void
     */
    public function __construct($keys = null)
    {
        if ($keys instanceof Arrayable) {
            $keys = $keys->toArray();
        }

        $keys = is_array($keys) ? $keys : func_get_args();

        if ($keys === []) {
            return $this->addRule('array');
        }

        $keys = array_map(
            static fn ($key) => enum_value($key),
            $keys,
        );

        return $this->addRule('array:'.implode(',', $keys));
    }

    /**
     * The field under validation must have a distinct values.
     *
     * @param  bool  $strict
     * @return $this
     */
    public function distinct(bool $strict = false)
    {
        return $this->addRule($strict ? 'distinct:strict' : 'distinct');
    }

    /**
     * The field under validation must have size less than or equal to a maximum value.
     *
     * @param  int  $max
     * @return $this
     */
    public function max(int $max)
    {
        return $this->addRule("max:$max");
    }

    /**
     * The field under validation must have size greater than or equal to a minimum value.
     *
     * @param  int  $min
     * @return $this
     */
    public function min(int $min)
    {
        return $this->addRule("min:$min");
    }

    /**
     * The field under validation must have a size matching the given value.
     *
     * @param  int  $size
     * @return $this
     */
    public function size(int $size)
    {
        return $this->addRule("size:$size");
    }

    /**
     * The field under validation must have a size between the given min and max.
     *
     * @param  int  $min
     * @param  int  $max
     * @return $this
     */
    public function between(int $min, int $max)
    {
        return $this->addRule("between:$min,$max");
    }

    /**
     * The field under validation must be an array that is a list.
     * An array is considered a list if its keys consist of consecutive numbers from 0 to count($array) - 1.
     *
     * @return $this
     */
    public function list()
    {
        return $this->addRule('list');
    }

    /**
     * The field under validation must be an array that contains all of the given parameter values.
     *
     * @param  array|string  $keys
     * @return $this
     */
    public function contains($keys)
    {
        if ($keys instanceof Arrayable) {
            $keys = $keys->toArray();
        }

        $keys = is_array($keys) ? $keys : func_get_args();

        $keys = array_map(
            static fn ($key) => enum_value($key),
            $keys,
        );

        return $this->addRule('contains:'.implode(',', $keys));
    }

    /**
     * The field under validation must exist in anotherfield's values.
     *
     * string $anotherField
     *
     * @return $this
     */
    public function inArray(string $anotherField)
    {
        return $this->addRule("in_array:$anotherField");
    }

    /**
     * Convert the rule to a validation string.
     */
    public function __toString(): string
    {
        return implode('|', array_unique($this->constraints));
    }

    /**
     * Add custom rules to the validation rules array.
     */
    protected function addRule(array|string $rules): ArrayRule
    {
        $this->constraints = array_merge($this->constraints, Arr::wrap($rules));

        return $this;
    }
}
