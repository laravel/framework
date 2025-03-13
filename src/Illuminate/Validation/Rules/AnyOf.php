<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidatorAwareRule;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

class AnyOf implements Rule, ValidatorAwareRule
{
    /**
     * The validator performing the validation.
     *
     * @var \Illuminate\Validation\Validator
     */
    protected $validator;

    /**
     * The error message after validation, if any.
     *
     * @var array
     */
    protected $messages = [];

    /**
     * The rules to match against.
     *
     * @var Illuminate\Contracts\Validation\ValidationRule[][]
     */
    private array $ruleSets = [];

    /**
     * Sets the validation rules to match against.
     *
     * @param  Illuminate\Contracts\Validation\ValidationRule[][]  $ruleSets
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($ruleSets)
    {
        if (! is_array($ruleSets)) {
            throw new InvalidArgumentException('The provided value must be an array of validation rules.');
        }
        $this->ruleSets = $ruleSets;
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
        $this->messages = [];

        foreach ($this->ruleSets as $ruleSet) {
            $validator = Validator::make(
                $value,
                $ruleSet,
                $this->validator->customMessages,
                $this->validator->customAttributes
            );

            if ($validator->passes()) {
                return true;
            }
        }

        $this->validator->addFailure($attribute, 'anyof');

        return false;
    }

    /**
     * Get the validation error message.
     *
     * @return array
     */
    public function message()
    {
        return $this->messages;
    }

    /**
     * Set the current validator.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return $this
     */
    public function setValidator($validator)
    {
        $this->validator = $validator;

        return $this;
    }
}
