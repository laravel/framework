<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidatorAwareRule;
use Illuminate\Support\Arr;
use TypeError;
use UnitEnum;

class Enum implements Rule, ValidatorAwareRule
{
    /**
     * The type of the enum.
     *
     * @var string
     */
    protected $type;

    /**
     * The current validator instance.
     *
     * @var \Illuminate\Validation\Validator
     */
    protected $validator;

    /**
     * Cases considered as valid.
     */
    private array $only = [];

    /**
     * Cases considered as invalid.
     */
    private array $except = [];

    /**
     * Create a new rule instance.
     *
     * @param  string  $type
     * @return void
     */
    public function __construct($type)
    {
        $this->type = $type;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if ($value instanceof $this->type) {
            return $this->isDesirable($value);
        }

        if (is_null($value) || ! enum_exists($this->type) || ! method_exists($this->type, 'tryFrom')) {
            return false;
        }

        try {
            return ! is_null($value = $this->type::tryFrom($value)) && $this->isDesirable($value);
        } catch (TypeError) {
            return false;
        }
    }

    /**
     * Get the validation error message.
     *
     * @return array
     */
    public function message()
    {
        $message = $this->validator->getTranslator()->get('validation.enum');

        return $message === 'validation.enum'
            ? ['The selected :attribute is invalid.']
            : $message;
    }

    /**
     * Set the current validator.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return $this
     */
    public function setValidator($validator)
    {
        $this->validator = $validator;

        return $this;
    }

    /**
     * Set specific cases to be valid.
     *
     * @param UnitEnum[]|UnitEnum $enums
     */
    public function only(array|UnitEnum $enums): static
    {
        $this->only = Arr::wrap($enums);

        return $this;
    }

    /**
     * Set specific cases to be invalid.
     *
     * @param UnitEnum[]|UnitEnum $enums
     */
    public function except(array|UnitEnum $enums): static
    {
        $this->except = Arr::wrap($enums);

        return $this;
    }

    private function isDesirable(mixed $value): bool
    {
        return match (true) {
            ! empty($this->only) => in_array(needle: $value, haystack: $this->only, strict: true),
            ! empty($this->except) => ! in_array(needle: $value, haystack: $this->except, strict: true),
            default => true,
        };
    }
}
