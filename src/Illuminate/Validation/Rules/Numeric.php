<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Support\Arr;
use Illuminate\Support\Traits\Conditionable;
use Stringable;

class Numeric implements Stringable
{
    use Conditionable;

    /**
     * The constraints for the number rule.
     */
    protected array $constraints = ['numeric'];

    /**
     * The field under validation must have a size between the given min and max (inclusive).
     *
     * @param  int|float  $min
     * @param  int|float  $max
     * @return $this
     */
    public function between(float|int $min, float|int $max): Numeric
    {
        return $this->addRule('between:'.$min.','.$max);
    }

    /**
     * The field under validation must contain the specified number of decimal places.
     *
     * @param  int  $min
     * @param  int|null  $max
     * @return $this
     */
    public function decimal(int $min, ?int $max = null): Numeric
    {
        $rule = 'decimal:'.$min;

        if ($max !== null) {
            $rule .= ','.$max;
        }

        return $this->addRule($rule);
    }

    /**
     * The field under validation must have a different value than field.
     *
     * @param  string  $field
     * @return $this
     */
    public function different(string $field): Numeric
    {
        return $this->addRule('different:'.$field);
    }

    /**
     * The integer under validation must have an exact length of value.
     *
     * @param  int  $length
     * @return $this
     */
    public function digits(int $length): Numeric
    {
        return $this->integer()->addRule('digits:'.$length);
    }

    /**
     * The integer under validation must have a length between the given min and max.
     *
     * @param  int  $min
     * @param  int  $max
     * @return $this
     */
    public function digitsBetween(int $min, int $max): Numeric
    {
        return $this->integer()->addRule('digits_between:'.$min.','.$max);
    }

    /**
     * The field under validation must be greater than the given field or value.
     *
     * @param  string  $field
     * @return $this
     */
    public function greaterThan(string $field): Numeric
    {
        return $this->addRule('gt:'.$field);
    }

    /**
     * The field under validation must be greater than or equal to the given field or value.
     *
     * @param  string  $field
     * @return $this
     */
    public function greaterThanOrEqual(string $field): Numeric
    {
        return $this->addRule('gte:'.$field);
    }

    /**
     * The field under validation must be an integer.
     *
     * @return $this
     */
    public function integer(): Numeric
    {
        return $this->addRule('integer');
    }

    /**
     * The field under validation must be less than the given field.
     *
     * @param  string  $field
     * @return $this
     */
    public function lessThan(string $field): Numeric
    {
        return $this->addRule('lt:'.$field);
    }

    /**
     * The field under validation must be less than or equal to the given field.
     *
     * @param  string  $field
     * @return $this
     */
    public function lessThanOrEqual(string $field): Numeric
    {
        return $this->addRule('lte:'.$field);
    }

    /**
     * The field under validation must be less than or equal to a maximum value.
     *
     * @param  float|int  $value
     * @return $this
     */
    public function max(float|int $value): Numeric
    {
        return $this->addRule('max:'.$value);
    }

    /**
     * The integer under validation must have a maximum length of value.
     *
     * @param  int  $value
     * @return $this
     */
    public function maxDigits(int $value): Numeric
    {
        return $this->addRule('max_digits:'.$value);
    }

    /**
     * The field under validation must have a minimum value.
     *
     * @param  float|int  $value
     * @return $this
     */
    public function min(float|int $value): Numeric
    {
        return $this->addRule('min:'.$value);
    }

    /**
     * The integer under validation must have a minimum length of value.
     *
     * @param  int  $value
     * @return $this
     */
    public function minDigits(int $value): Numeric
    {
        return $this->addRule('min_digits:'.$value);
    }

    /**
     * The field under validation must be a multiple of value.
     *
     * @param  float|int  $value
     * @return $this
     */
    public function multipleOf(float|int $value): Numeric
    {
        return $this->addRule('multiple_of:'.$value);
    }

    /**
     * The given field must match the field under validation.
     *
     * @param  string  $field
     * @return $this
     */
    public function same(string $field): Numeric
    {
        return $this->addRule('same:'.$field);
    }

    /**
     * The field under validation must have a size matching the given value.
     *
     * @param  int  $value
     * @return $this
     */
    public function size(int $value): Numeric
    {
        return $this->integer()->addRule('size:'.$value);
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
    protected function addRule(array|string $rules): Numeric
    {
        $this->constraints = array_merge($this->constraints, Arr::wrap($rules));

        return $this;
    }
}
