<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidatorAwareRule;
use Illuminate\Validation\Validator;
use InvalidArgumentException;

class OneOf implements Rule, ValidatorAwareRule
{
    /**
     * The name of the rule.
     *
     * @var string
     */
    protected $rule = 'oneof';

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
            $this->validator->setRules($ruleSet);
            $this->validator->setData($value);
            if ($this->validator->passes()) {
                return true;
            }
        }

        array_push($this->messages, "The {$attribute} field does not match any of the allowed rule sets.");

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
