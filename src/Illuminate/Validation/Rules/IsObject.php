<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidatorAwareRule;
use Illuminate\Support\Traits\Conditionable;
use TypeError;

class IsObject implements Rule, ValidatorAwareRule
{
    use Conditionable;

    /**
     * The class of the object to validate against.
     *
     * @var class-string
     */
    protected $object;

    /**
     * Whether to check each item of an iterable.
     *
     * @var bool
     */
    protected $isIterable = false;

    /**
     * Whether to enforce strict checking of the object type
     * meaning that null values will fail the validation.
     *
     * @var bool
     */
    protected $strict = false;

    /**
     * The current validator instance.
     *
     * @var \Illuminate\Validation\Validator
     */
    protected $validator;

    /**
     * Create a new rule instance.
     *
     * @param class-string $object
     */
    public function __construct($object)
    {
        if (! is_string($object)) {
            throw new TypeError('The object must be a class string.');
        }

        $this->object = $object;
    }

    /**
     * Set the rule to strictly check the
     *
     * @return $this
     */
    public function strict()
    {
        $this->strict = true;

        return $this;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed  $value
     *
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if (is_iterable($value)) {
            $this->isIterable = true;

            if (count($value) < 1) {
                return false;
            }

            foreach ($value as $item) {
                if (is_null($item) && count($value) < 2) {
                    return false;
                }

                if (is_null($item) && ! $this->strict) {
                    return true;
                }

                if (! $item instanceof $this->object) {
                    return false;
                }
            }

            return true;
        }

        return $value instanceof $this->object;
    }

    /**
     * Get the validation error message.
     *
     * @return array
     */
    public function message()
    {
        if ($this->isIterable) {
            $message = $this->validator->getTranslator()->get('validation.is_object.iterable');

            return $message === 'validation.is_object.iterable'
                ? ['The field :attribute contains invalid instances.']
                : $message;
        }

        $message = $this->validator->getTranslator()->get('validation.is_object.single');

        return $message === 'validation.is_object.single'
            ? ['The field :attribute is not a valid instance.']
            : $message;
    }

    /**
     * Set the current validator.
     *
     * @param \Illuminate\Validation\Validator $validator
     *
     * @return $this
     */
    public function setValidator($validator)
    {
        $this->validator = $validator;

        return $this;
    }
}
