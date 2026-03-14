<?php

namespace Illuminate\Validation\Rules;

use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\ValidatorAwareRule;
use Illuminate\Support\Arr;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Validation\Validator;

class ListRule implements ValidationRule, DataAwareRule, ValidatorAwareRule
{
    use Conditionable;

    /**
     * The rules that each item in the list must pass.
     */
    protected array $itemRules = [];

    /**
     * The minimum number of items in the list.
     */
    protected ?int $min = null;

    /**
     * The maximum number of items in the list.
     */
    protected ?int $max = null;

    /**
     * The exact number of items the list must contain.
     */
    protected ?int $size = null;

    /**
     * The distinct validation mode (null, '', 'strict', or 'ignore_case').
     */
    protected ?string $distinctMode = null;

    /**
     * The data under validation.
     */
    protected array $data = [];

    /**
     * The validator performing the validation.
     */
    protected Validator $validator;

    /**
     * The list must have a number of items between the given min and max (inclusive).
     *
     * @param  int  $min
     * @param  int  $max
     * @return $this
     */
    public function between(int $min, int $max): static
    {
        $this->min = $min;
        $this->max = $max;
        $this->size = null;

        return $this;
    }

    /**
     * The list items must all be distinct.
     *
     * @return $this
     */
    public function distinct(): static
    {
        $this->distinctMode = '';

        return $this;
    }

    /**
     * The list items must all be distinct using strict comparison.
     *
     * @return $this
     */
    public function distinctStrict(): static
    {
        $this->distinctMode = 'strict';

        return $this;
    }

    /**
     * The list items must all be distinct (case-insensitive).
     *
     * @return $this
     */
    public function distinctIgnoreCase(): static
    {
        $this->distinctMode = 'ignore_case';

        return $this;
    }

    /**
     * The list must contain exactly the given number of items.
     *
     * @param  int  $value
     * @return $this
     */
    public function size(int $value): static
    {
        $this->size = $value;
        $this->min = null;
        $this->max = null;

        return $this;
    }

    /**
     * The list must not have more than the given number of items.
     *
     * @param  int  $value
     * @return $this
     */
    public function max(int $value): static
    {
        $this->max = $value;
        $this->size = null;

        return $this;
    }

    /**
     * The list must have at least the given number of items.
     *
     * @param  int  $value
     * @return $this
     */
    public function min(int $value): static
    {
        $this->min = $value;
        $this->size = null;

        return $this;
    }

    /**
     * Each item in the list must pass the given validation rules.
     *
     * @param  array|string  $rules
     * @return $this
     */
    public function of(array|string $rules): static
    {
        $this->itemRules = Arr::wrap($rules);

        return $this;
    }

    /**
     * Set the data under validation.
     *
     * @param  array  $data
     * @return $this
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Set the current validator.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return $this
     */
    public function setValidator(Validator $validator): static
    {
        $this->validator = $validator;

        return $this;
    }

    /**
     * Run the validation rule.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($value) || ! array_is_list($value)) {
            $fail('validation.list');

            return;
        }

        $count = count($value);

        if ($this->size !== null && $count !== $this->size) {
            $fail('validation.size.array')->translate(['size' => $this->size]);
        }

        if ($this->min !== null && $count < $this->min) {
            $fail('validation.min.array')->translate(['min' => $this->min]);
        }

        if ($this->max !== null && $count > $this->max) {
            $fail('validation.max.array')->translate(['max' => $this->max]);
        }

        $itemRules = $this->itemRules;

        if ($this->distinctMode !== null) {
            $itemRules[] = $this->distinctMode !== '' ? 'distinct:'.$this->distinctMode : 'distinct';
        }

        if (empty($itemRules)) {
            return;
        }

        $subValidator = new Validator(
            $this->validator->getTranslator(),
            $this->data,
            [$attribute.'.*' => $itemRules],
            $this->validator->customMessages,
        );

        $subValidator->addCustomAttributes($this->validator->customAttributes);

        $this->validator->messages()->merge($subValidator->messages());
    }
}
