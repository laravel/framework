<?php

namespace Illuminate\Validation\Rules;

use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidatorAwareRule;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use InvalidArgumentException;

class Email implements Rule, DataAwareRule, ValidatorAwareRule
{
    use Conditionable, Macroable;

    /**
     * @var bool|\Closure(string, mixed): bool
     */
    public bool|Closure $validateMxRecord = false;
    /**
     * @var bool|\Closure(string, mixed): bool
     */
    public bool|Closure $preventSpoofing = false;
    /**
     * @var bool|\Closure(string, mixed): bool
     */
    public bool|Closure $nativeValidation = false;
    /**
     * @var bool|\Closure(string, mixed): bool
     */
    public bool|Closure $nativeValidationWithUnicodeAllowed = false;
    /**
     * @var bool|\Closure(string, mixed): bool
     */
    public bool|Closure $rfcCompliant = false;
    /**
     * @var bool|\Closure(string, mixed): bool
     */
    public bool|Closure $strictRfcCompliant = false;

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
     * An array of custom rules that will be merged into the validation rules.
     *
     * @var array
     */
    protected $customRules = [];

    /**
     * The error message after validation, if any.
     *
     * @var array
     */
    protected $messages = [];

    /**
     * The callback that will generate the "default" version of the file rule.
     *
     * @var string|array|callable|null
     */
    public static $defaultCallback;

    /**
     * Set the default callback to be used for determining the email default rules.
     *
     * If no arguments are passed, the default email rule configuration will be returned.
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
            throw new InvalidArgumentException('The given callback should be callable or an instance of '.static::class);
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
        $email = is_callable(static::$defaultCallback)
            ? call_user_func(static::$defaultCallback)
            : static::$defaultCallback;

        return $email instanceof static ? $email : new static;
    }

    /**
     * Ensure that the email is an RFC compliant email address.
     *
     * @param  bool  $strict
     * @param  bool|\Closure(string, mixed): bool  $condition
     * @return $this
     */
    public function rfcCompliant(bool $strict = false, bool|Closure $condition = true)
    {
        if ($strict) {
            $this->strictRfcCompliant = $condition;
        } else {
            $this->rfcCompliant = $condition;
        }

        return $this;
    }

    /**
     * Ensure that the email is a strictly enforced RFC compliant email address.
     *
     * @param  bool|\Closure(string, mixed): bool  $condition
     * @return $this
     */
    public function strict(bool|Closure $condition = true)
    {
        return $this->rfcCompliant(strict: true, condition: $condition);
    }

    /**
     * Ensure that the email address has a valid MX record.
     *
     * Requires the PHP intl extension.
     *
     * @param  bool|\Closure(string, mixed): bool  $condition
     * @return $this
     */
    public function validateMxRecord(bool|Closure $condition = true)
    {
        $this->validateMxRecord = $condition;

        return $this;
    }

    /**
     * Ensure that the email address is not attempting to spoof another email address using invalid unicode characters.
     *
     * @param  bool|\Closure(string, mixed): bool  $condition
     * @return $this
     */
    public function preventSpoofing(bool|Closure $condition = true)
    {
        $this->preventSpoofing = $condition;

        return $this;
    }

    /**
     * Ensure the email address is valid using PHP's native email validation functions.
     *
     * @param  bool  $allowUnicode
     * @param  bool|\Closure(string, mixed): bool  $condition
     * @return $this
     */
    public function withNativeValidation(bool $allowUnicode = false, bool|Closure $condition = true)
    {
        if ($allowUnicode) {
            $this->nativeValidationWithUnicodeAllowed = $condition;
        } else {
            $this->nativeValidation = $condition;
        }

        return $this;
    }

    /**
     * Specify additional validation rules that should be merged with the default rules during validation.
     *
     * @param  string|array  $rules
     * @return $this
     */
    public function rules($rules)
    {
        $this->customRules = array_merge($this->customRules, Arr::wrap($rules));

        return $this;
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

        if (! is_string($value) && ! (is_object($value) && method_exists($value, '__toString'))) {
            return false;
        }

        $validator = Validator::make(
            $this->data,
            [$attribute => $this->buildValidationRules($attribute, $value)],
            $this->validator->customMessages,
            $this->validator->customAttributes
        );

        if ($validator->fails()) {
            $this->messages = array_merge($this->messages, $validator->messages()->all());

            return false;
        }

        return true;
    }

    /**
     * Build the array of underlying validation rules based on the current state.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return array
     */
    public function buildValidationRules($attribute, $value)
    {
        $rules = [];

        if (value($this->rfcCompliant, $attribute, $value)) {
            $rules[] = 'rfc';
        }

        if (value($this->strictRfcCompliant, $attribute, $value)) {
            $rules[] = 'strict';
        }

        if (value($this->validateMxRecord, $attribute, $value)) {
            $rules[] = 'dns';
        }

        if (value($this->preventSpoofing, $attribute, $value)) {
            $rules[] = 'spoof';
        }

        if (value($this->nativeValidation, $attribute, $value)) {
            $rules[] = 'filter';
        }

        if (value($this->nativeValidationWithUnicodeAllowed, $attribute, $value)) {
            $rules[] = 'filter_unicode';
        }

        if ($rules) {
            $rules = ['email:'.implode(',', $rules)];
        } else {
            $rules = ['email'];
        }

        return array_merge(array_filter($rules), $this->customRules);
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

    /**
     * Set the current data under validation.
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
