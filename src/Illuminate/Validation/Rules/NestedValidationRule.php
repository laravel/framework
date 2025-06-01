<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidatorAwareRule;
use Illuminate\Validation\Validator;

class NestedValidationRule implements Rule, ValidatorAwareRule, DataAwareRule
{
    /**
     * The validator instance.
     *
     * @var \Illuminate\Validation\Validator
     */
    protected $validator;

    /**
     * The data under validation.
     *
     * @var array
     */
    protected $data = [];

    /**
     * The schema for nested validation.
     *
     * @var string|array
     */
    protected $schema;

    /**
     * The conditional validation rules.
     *
     * @var array
     */
    protected $conditions;

    /**
     * Create a new nested validation rule instance.
     *
     * @param  string|array  $schema
     * @param  array  $conditions
     */
    public function __construct($schema, array $conditions = [])
    {
        $this->schema = $schema;
        $this->conditions = $conditions;
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
        if (! $this->validator) {
            return false;
        }

        return $this->validator->validateNestedStructure($attribute, $value, $this->schema, $this->conditions);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :attribute field contains invalid nested data.';
    }

    /**
     * Set the validator instance.
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
     * Set the data under validation.
     *
     * @param  array  $data
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }
}
