<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidatorAwareRule;
use Illuminate\Support\Arr;
use Illuminate\Validation\Validator;

class Nested implements Rule, ValidatorAwareRule, DataAwareRule
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
     * Create a new nested validation rule instance.
     *
     * @param  string|array  $schema
     */
    public function __construct($schema)
    {
        $this->schema = $schema;
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

        // Allow null values to pass if not required
        if (is_null($value)) {
            return true;
        }

        try {
            // Parse schema if it's a JSON string
            $schema = $this->schema;
            if (is_string($schema)) {
                $schema = json_decode($schema, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return false;
                }
            }

            // Use internal method which doesn't throw exceptions
            return $this->validator->validateNestedStructureInternal($attribute, $value, $schema, []);
        } catch (\Exception $e) {
            // Add error messages but don't throw exceptions
            if (! $this->validator->messages) {
                $this->validator->messages = new \Illuminate\Support\MessageBag;
            }

            $this->validator->messages->add($attribute, 'The ' . $attribute . ' field contains invalid nested data.');
            return false;
        }
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
