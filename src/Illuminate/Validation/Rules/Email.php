<?php

namespace Illuminate\Validation\Rules;

use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\DNSCheckValidation;
use Egulias\EmailValidator\Validation\Extra\SpoofCheckValidation;
use Egulias\EmailValidator\Validation\NoRFCWarningsValidation;
use Egulias\EmailValidator\Validation\RFCValidation;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidatorAwareRule;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Validation\Concerns\FilterEmailValidation;
use InvalidArgumentException;

class Email implements Rule, DataAwareRule, ValidatorAwareRule
{
    use Conditionable;

    /**
     * The data under validation.
     *
     * @var array
     */
    protected $data;

    /**
     * The validator performing the validation.
     *
     * @var \Illuminate\Contracts\Validation\Validator
     */
    protected $validator;

    /**
     * Additional validation rules that should be merged into the default rules during validation.
     *
     * @var array
     */
    protected $customRules = [];

    /**
     * The callback that will generate the "default" version of the password rule.
     *
     * @var string|array|callable|null
     */
    public static $defaultCallback;

    /**
     * The default validation rule.
     *
     * @var bool
     */
    protected $rfc = false;

    /**
     * If NoRFCWarningsValidation should be used.
     *
     * @var bool
     */
    protected $strict = false;

    /**
     * If DNSCheckValidation should be used.
     *
     * @var bool
     */
    protected $dns = false;

    /**
     * If SpoofCheckValidation should be used.
     *
     * @var bool
     */
    protected $spoof = false;

    /**
     * If FilterEmailValidation should be used.
     *
     * @var bool
     */
    protected $filter = false;

    public function __construct()
    {
        $this->rfc = true;
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

        $validator = Validator::make(
            $this->data,
            [$attribute => array_merge(['string'], $this->customRules)],
            $this->validator->customMessages,
            $this->validator->customAttributes
        )->after(function ($validator) use ($attribute, $value) {
            if (! is_string($value) && ! (is_object($value) && method_exists($value, '__toString'))) {
                return;
            }

            $email_validator = new EmailValidator;

            if ($this->rfc && ! $email_validator->isValid($value, new RFCValidation)) {
                $validator->errors()->add(
                    $attribute,
                    $this->getErrorMessage('validation.email.rfc')
                );
            }

            if ($this->strict && ! $email_validator->isValid($value, new NoRFCWarningsValidation)) {
                $validator->errors()->add(
                    $attribute,
                    $this->getErrorMessage('validation.email.strict')
                );
            }

            if ($this->dns && ! $email_validator->isValid($value, new DNSCheckValidation)) {
                $validator->errors()->add(
                    $attribute,
                    $this->getErrorMessage('validation.email.dns')
                );
            }

            if ($this->spoof && ! $email_validator->isValid($value, new SpoofCheckValidation)) {
                $validator->errors()->add(
                    $attribute,
                    $this->getErrorMessage('validation.email.spoof')
                );
            }

            if ($this->filter && ! $email_validator->isValid($value, new FilterEmailValidation)) {
                $validator->errors()->add(
                    $attribute,
                    $this->getErrorMessage('validation.email.filter')
                );
            }
        });

        if ($validator->fails()) {
            return $this->fail($validator->messages()->all());
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
     * Get the translated password error message.
     *
     * @param  string  $key
     * @return string
     */
    protected function getErrorMessage($key)
    {
        $messages = [
            'validation.email.rfc' => 'The :attribute must be a valid email address.',
            'validation.email.strict' => 'The :attribute must be a valid email address.',
            'validation.email.dns' => 'The :attribute does not have a valid domain.',
            'validation.email.spoof' => 'The :attribute appears to be spoofed.',
            'validation.email.filter' => 'The :attribute must pass the filter.',
        ];

        if (($message = $this->validator->getTranslator()->get($key)) !== $key) {
            return $message;
        }

        return $messages[$key];
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

    /**
     * Set the performing validator.
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
     * Specify additional validation rules that should be merged with the default rules during validation.
     *
     * @param  string|array  $rules
     * @return $this
     */
    public function rules($rules)
    {
        $this->customRules = Arr::wrap($rules);

        return $this;
    }

    /**
     * Adds the given failures, and return false.
     *
     * @param  array|string  $messages
     * @return bool
     */
    protected function fail($messages)
    {
        $messages = collect(Arr::wrap($messages))->map(function ($message) {
            return $this->validator->getTranslator()->get($message);
        })->all();

        $this->messages = array_merge($this->messages, $messages);

        return false;
    }

    /**
     * Set the default callback to be used for determining a password's default rules.
     *
     * If no arguments are passed, the default password rule configuration will be returned.
     *
     * @param  static|callable|null  $callback
     * @return static|null
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
     * Get the default configuration of the password rule.
     *
     * @return static
     */
    public static function default()
    {
        $email = is_callable(static::$defaultCallback)
            ? call_user_func(static::$defaultCallback)
            : static::$defaultCallback;

        return $email instanceof Rule ? $email : static::rfc();
    }

    public static function rfc()
    {
        return new static();
    }

    public function strict()
    {
        $this->strict = true;

        return $this;
    }

    public function dns()
    {
        $this->dns = true;

        return $this;
    }

    public function spoof()
    {
        $this->spoof = true;

        return $this;
    }

    public function filter()
    {
        $this->filter = true;

        return $this;
    }
}
