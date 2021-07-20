<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidatorAwareRule;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Traits\Conditionable;

class ArrayRule implements Rule, ValidatorAwareRule
{
    use Conditionable;

    /**
     * The validator performing the validation.
     *
     * @var \Illuminate\Contracts\Validation\Validator
     */
    protected $validator;

    /**
     * The rules the array keys must adhere to.
     *
     * @var array
     */
    protected $keyRules = [];

    /**
     * The only keys that may be present on the array.
     *
     * @var array
     */
    protected $keysIn = [];

    /**
     * The keys that may not be present on the array.
     *
     * @var array
     */
    protected $keysNotIn = [];

    /**
     * The minimum number of elements the array can have.
     *
     * @var int
     */
    protected $min;

    /**
     * The maximum number of elements the array can have.
     *
     * @var int
     */
    protected $max;

    /**
     * The number of elements the array must have.
     *
     * @var int
     */
    protected $size;

    /**
     * Indicates that unvalidated array keys should be excluded, even if the parent array was validated.
     * Will use the default value from the validator if left unset.
     *
     * @var bool
     */
    public $excludeUnvalidatedArrayKeys;

    /**
     * The failure messages, if any.
     *
     * @var array
     */
    protected $messages = [];

    /**
     * Defines the rules against which the keys should be validated.
     *
     * @param  string|array|Rule|\Closure  $rules
     * @return $this
     */
    public function keyRules($rules)
    {
        $this->keyRules = is_array($rules) ? $rules : func_get_args();

        return $this;
    }

    /**
     * Requires the keys to be in the given set.
     *
     * @param  string|array  $keys
     * @return $this
     */
    public function keysIn($keys)
    {
        $this->keysNotIn = [];

        $this->keysIn = is_array($keys) ? $keys : func_get_args();

        return $this;
    }

    /**
     * Requires the keys to be in the given set.
     *
     * @param  string|array  $keys
     * @return $this
     */
    public function keysNotIn($keys)
    {
        $this->keysIn = [];

        $this->keysNotIn = is_array($keys) ? $keys : func_get_args();

        return $this;
    }

    /**
     * Forces unvalidated array keys to be excluded from the validated array.
     *
     * @return $this
     */
    public function excludeUnvalidatedKeys()
    {
        $this->excludeUnvalidatedArrayKeys = true;

        return $this;
    }

    /**
     * Forces unvalidated array keys to be returned from the validated array.
     *
     * @return $this
     */
    public function includeUnvalidatedKeys()
    {
        $this->excludeUnvalidatedArrayKeys = false;

        return $this;
    }

    /**
     * The minimum number of elements the array can have.
     *
     * @param  int|null  $value
     * @return $this
     */
    public function min(?int $value)
    {
        $this->min = $value;

        return $this;
    }

    /**
     * The maximum number of elements the array can have.
     *
     * @param  int|null  $value
     * @return $this
     */
    public function max(?int $value)
    {
        $this->max = $value;

        return $this;
    }

    /**
     * The number of elements the array must have.
     *
     * @param  int|null  $value
     * @return $this
     */
    public function size(?int $value)
    {
        $this->size = $value;

        return $this;
    }

    /**
     * Set the performing validator.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     *
     * @return $this
     */
    public function setValidator($validator)
    {
        $this->validator = $validator;

        return $this;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return  bool
     */
    public function passes($attribute, $value)
    {
        if (! is_array($value)) {
            return $this->fail('validation.array');
        }

        $size = count($value);

        if (isset($this->min) && $size < $this->min) {
            $this->fail($this->translate('validation.min.array', ['min' => $this->min]));
        }

        if (isset($this->max) && $size > $this->max) {
            $this->fail($this->translate('validation.max.array', ['max' => $this->max]));
        }

        if (isset($this->size) && $size !== $this->size) {
            $this->fail($this->translate('validation.size.array', ['size' => $this->size]));
        }

        if (! empty($this->keysIn) && ! empty(array_diff_key($value, array_fill_keys($this->keysIn, '')))) {
            $this->fail('validation.array');
        }

        if (! empty($this->keysNotIn) && ! empty(array_intersect_key($value, array_fill_keys($this->keysNotIn, '')))) {
            $this->fail('validation.array');
        }

        if (! empty($this->keyRules)) {
            $keys = array_keys($value);

            $validator = Validator::make(
                array_combine($keys, $keys),
                array_fill_keys($keys, $this->keyRules),
                [],
                array_fill_keys($keys, $this->translate(":attribute key ':key'"))
            );

            if ($validator->fails()) {
                $this->fail($validator->messages()->all());
            }
        }

        if (! empty($this->messages)) {
            return false;
        }

        return true;
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
     * Adds the given failures, and return false.
     *
     * @param  array|string  $messages
     *
     * @return bool
     */
    protected function fail($messages)
    {
        $messages = collect(Arr::wrap($messages))->map(function ($message) {
            return $this->translate($message);
        })->all();

        $this->messages = array_merge($this->messages, $messages);

        return false;
    }

    /**
     * Translate a message.
     *
     * @return string
     */
    protected function translate($message, $replace = [])
    {
        return $this->validator->getTranslator()->get($message, $replace);
    }
}
