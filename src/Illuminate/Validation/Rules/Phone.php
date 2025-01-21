<?php

namespace Illuminate\Validation\Rules;

use Error;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidatorAwareRule;
use Illuminate\Support\Arr;
use Illuminate\Support\PhoneNumber;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use InvalidArgumentException;

class Phone implements Rule, ValidatorAwareRule, DataAwareRule
{
    use Conditionable, Macroable;

    /**
     * The validator performing the validation.
     *
     * @var \Illuminate\Validation\Validator
     */
    protected $validator;

    /**
     * The data under validation.
     *
     * @var array
     */
    protected $data;

    /**
     * The name of the country field.
     *
     * @var string
     */
    protected $countryField;

    /**
     * The callback that will generate the "default" version of the file rule.
     *
     * @var string|array|callable|null
     */
    public static $defaultCallback;

    /**
     * Set the default callback to be used for determining the phone default rules.
     *
     * If no arguments are passed, the default phone rule configuration will be returned.
     *
     * @param  static|callable|null  $callback
     * @return static|void
     */
    public static function defaults($callback = null)
    {
        if (is_null($callback)) {
            return static::default();
        }

        if (! is_callable($callback) && ! $callback instanceof static) {
            throw new InvalidArgumentException('The given callback should be callable or an instance of ' . static::class);
        }

        static::$defaultCallback = $callback;
    }

    /**
     * Get the default configuration of the file rule.
     *
     * @return static
     */
    public static function default()
    {
        $phone = is_callable(static::$defaultCallback)
            ? call_user_func(static::$defaultCallback)
            : static::$defaultCallback;

        return $phone instanceof static ? $phone : new static;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     */
    public function passes($attribute, $value): bool
    {
        $country = $this->getCountryFieldValue($attribute);

        try {
            $phone = PhoneNumber::of($value, $country);

            return $phone->isValid();
        } catch (Error) {
            return false;
        }
    }

    /**
     * Set the name of the country field.
     */
    public function countryField(string $name): static
    {
        $this->countryField = $name;

        return $this;
    }

    /**
     * Get the value of the country field.
     */
    protected function getCountryFieldValue(string $attribute)
    {
        return Arr::get($this->data, $this->countryField ?: $attribute . '_country');
    }

    /**
     * Get the validation error message.
     *
     * @return array
     */
    public function message()
    {
        $message = $this->validator->getTranslator()->get('validation.phone');

        return $message === 'validation.phone'
            ? ['The :attribute field must be a valid phone number.']
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
