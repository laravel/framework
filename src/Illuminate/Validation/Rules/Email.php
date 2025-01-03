<?php

namespace Illuminate\Validation\Rules;

use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\DNSCheckValidation;
use Egulias\EmailValidator\Validation\Extra\SpoofCheckValidation;
use Egulias\EmailValidator\Validation\MultipleValidationWithAnd;
use Egulias\EmailValidator\Validation\NoRFCWarningsValidation;
use Egulias\EmailValidator\Validation\RFCValidation;
use Illuminate\Container\Container;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidatorAwareRule;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Validation\Concerns\FilterEmailValidation;
use InvalidArgumentException;

class Email implements Rule, DataAwareRule, ValidatorAwareRule
{
    use Conditionable, Macroable;

    public bool $rfcCompliantWithoutWarnings = false;

    public bool $domainExists = false;

    public bool $preventEmailSpoofing = false;

    public bool $basicFormat = false;

    public bool $basicFormatUnicodeAllowed = false;

    public bool $rfcCompliant = false;

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
     * The data under validation.
     *
     * @var array
     */
    protected $data;

    /**
     * The validator performing the validation.
     *
     * @var \Illuminate\Validation\Validator
     */
    protected $validator;

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

        return $email instanceof self ? $email : new self();
    }

    /**
     * A strict rule set which includes DNS and Spoof checks.
     *
     * @return static
     */
    public static function strictSecurity()
    {
        return (new self())
            ->rfcCompliantWithoutWarnings()
            ->domainExists()
            ->preventEmailSpoofing();
    }

    /**
     * Validate against RFCValidation.
     *
     * @return $this
     */
    public function rfcCompliant()
    {
        $this->rfcCompliant = true;

        return $this;
    }

    /**
     * Validate against NoRFCWarningsValidation.
     *
     * @return $this
     */
    public function rfcCompliantWithoutWarnings()
    {
        $this->rfcCompliantWithoutWarnings = true;

        return $this;
    }

    /**
     * Validate against DNSCheckValidation.
     * Requires the PHP intl extension.
     *
     * @return $this
     */
    public function domainExists()
    {
        $this->domainExists = true;

        return $this;
    }

    /**
     * Validate against SpoofCheckValidation.
     * Requires the PHP intl extension.
     *
     * @return $this
     */
    public function preventEmailSpoofing()
    {
        $this->preventEmailSpoofing = true;

        return $this;
    }

    /**
     * Validate against FilterEmailValidation.
     *
     * @return $this
     */
    public function basicFormat()
    {
        $this->basicFormat = true;

        return $this;
    }

    /**
     * Validate against FilterEmailValidation::unicode().
     *
     * @return $this
     */
    public function basicFormatUnicodeAllowed()
    {
        $this->basicFormatUnicodeAllowed = true;

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

        $emailValidator = Container::getInstance()->make(EmailValidator::class);

        $passes = $emailValidator->isValid((string) $value, new MultipleValidationWithAnd($this->buildValidationRules()));

        if (! $passes) {
            $this->messages = [trans('validation.email', ['attribute' => $attribute])];

            return false;
        }

        if ($this->customRules) {
            $validator = Validator::make(
                $this->data,
                [$attribute => $this->customRules],
                $this->validator->customMessages,
                $this->validator->customAttributes
            );

            if ($validator->fails()) {
                return $this->fail($validator->messages()->all());
            }
        }

        return true;
    }

    /**
     * Build the array of underlying validation rules based on the current state.
     *
     * @return array
     */
    protected function buildValidationRules()
    {
        $rules = [];

        if ($this->rfcCompliant) {
            $rules[] = new RFCValidation;
        }

        if ($this->rfcCompliantWithoutWarnings) {
            $rules[] = new NoRFCWarningsValidation;
        }

        if ($this->domainExists) {
            $rules[] = new DNSCheckValidation;
        }

        if ($this->preventEmailSpoofing) {
            $rules[] = new SpoofCheckValidation;
        }

        if ($this->basicFormat) {
            $rules[] = new FilterEmailValidation;
        }

        if ($this->basicFormatUnicodeAllowed) {
            $rules[] = FilterEmailValidation::unicode();
        }

        if ($rules) {
            return $rules;
        }

        return [new RFCValidation];
    }

    /**
     * Adds the given failures, and return false.
     *
     * @param  array|string  $messages
     * @return bool
     */
    protected function fail($messages)
    {
        $messages = Collection::wrap($messages)
            ->map(fn ($message) => $this->validator->getTranslator()->get($message))
            ->all();

        $this->messages = array_merge($this->messages, $messages);

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
