<?php

namespace Illuminate\Validation\Rules;

use Closure;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Contracts\Validation\RequiresPreviousRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\ValidatorAwareRule;
use Illuminate\Validation\Validator;

class ExistsUsingBuilder implements ValidationRule, ValidatorAwareRule, RequiresPreviousRule
{
    /**
     * The current validator.
     *
     * @var \Illuminate\Validation\Validator
     */
    protected Validator $validator;

    /**
     * Create a new rule instance.
     *
     * @param  \Illuminate\Contracts\Database\Query\Builder  $query
     * @param  string  $column
     *
     * @retun void
     */
    public function __construct(
        protected Builder $query,
        protected string $column,
    ) {
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
        $column = $this->column === 'NULL'
            ? $this->query->qualifyColumn($this->validator->guessColumnForQuery($attribute))
            : $this->query->qualifyColumn($this->column);

        if (is_array($value)) {
            $expectedCount = count(array_unique($value));

            if (
                $expectedCount > 0
                && $this->query->clone()->whereIn($column, $value)->distinct()->count($column) < $expectedCount
            ) {
                $fail('validation.exists')->translate();
            }
        } elseif ($this->query->clone()->where($column, $value)->doesntExist()) {
            $fail('validation.exists')->translate();
        }
    }

    /**
     * Set the current validator.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return $this
     */
    public function setValidator(Validator $validator): self
    {
        $this->validator = $validator;

        return $this;
    }
}
