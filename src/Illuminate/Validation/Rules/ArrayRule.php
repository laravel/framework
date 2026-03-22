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
     * Add a custom validation rule.
     *
     * @param  array|string  $rules
     * @return $this
     */
    public function rule(array|string $rules): static
    {
        return $this->addRule($rules);
    }

    /**
     * Convert the rule to a validation string.
     *
     * @return string
     */
    public function __toString()
    {
        if (empty($this->keys)) {
            $base = 'array';
        } else {
            $keys = array_map(
                static fn ($key) => enum_value($key),
                $this->keys,
            );

            $base = 'array:'.implode(',', $keys);
        }

        if (empty($this->constraints)) {
            return $base;
        }

        return $base.'|'.implode('|', array_unique($this->constraints));
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
