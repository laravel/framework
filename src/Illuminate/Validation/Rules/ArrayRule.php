<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Stringable;

use function Illuminate\Support\enum_value;

class ArrayRule implements Stringable
{
    /**
     * The accepted keys.
     *
     * @var array
     */
    protected $keys;

    /**
     * The additional constraints for the array rule.
     */
    protected array $constraints = [];

    /**
     * Create a new array rule instance.
     *
     * @param  \Illuminate\Contracts\Support\Arrayable|array|null  $keys
     */
    public function __construct($keys = null)
    {
        if ($keys instanceof Arrayable) {
            $keys = $keys->toArray();
        }

        $this->keys = is_array($keys) ? $keys : func_get_args();
    }

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
     * The field under validation must have a different value than the given field.
     *
     * @param  string  $field
     * @return $this
     */
    public function different(string $field): static
    {
        return $this->addRule('different:'.$field);
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
     * The field under validation must have more items than the given field.
     *
     * @param  string  $field
     * @return $this
     */
    public function greaterThan(string $field): static
    {
        return $this->addRule('gt:'.$field);
    }

    /**
     * The field under validation must have at least as many items as the given field.
     *
     * @param  string  $field
     * @return $this
     */
    public function greaterThanOrEqualTo(string $field): static
    {
        return $this->addRule('gte:'.$field);
    }

    /**
     * The field under validation must have fewer items than the given field.
     *
     * @param  string  $field
     * @return $this
     */
    public function lessThan(string $field): static
    {
        return $this->addRule('lt:'.$field);
    }

    /**
     * The field under validation must have no more items than the given field.
     *
     * @param  string  $field
     * @return $this
     */
    public function lessThanOrEqualTo(string $field): static
    {
        return $this->addRule('lte:'.$field);
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
     * The field under validation must match the given field.
     *
     * @param  string  $field
     * @return $this
     */
    public function same(string $field): static
    {
        return $this->addRule('same:'.$field);
    }

    /**
     * Convert the rule to a validation string.
     *
     * @return string
     */
    public function __toString()
    {
        return implode('|', array_unique([$this->arrayRule(), ...$this->constraints]));
    }

    /**
     * Get the array rule with its accepted keys.
     */
    protected function arrayRule(): string
    {
        if (empty($this->keys)) {
            return 'array';
        }

        $keys = array_map(
            static fn ($key) => enum_value($key),
            $this->keys,
        );

        return 'array:'.implode(',', $keys);
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
