<?php

namespace Illuminate\Validation;

use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ImplicitRule;
use Illuminate\Contracts\Validation\InvokableRule;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\ValidatorAwareRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class InvokableValidationRule implements Rule, ValidatorAwareRule
{
    /**
     * The invokable that validates the attribute.
     *
     * @var \Illuminate\Contracts\Validation\ValidationRule|\Illuminate\Contracts\Validation\InvokableRule
     */
    protected $invokable;

    /**
     * Indicates if the validation invokable failed.
     *
     * @var bool
     */
    protected $failed = false;

    /**
     * The validation error messages.
     *
     * @var array
     */
    protected $messages = [];

    /**
     * The current validator.
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
     * Create a new explicit Invokable validation rule.
     *
     * @param  \Illuminate\Contracts\Validation\ValidationRule|\Illuminate\Contracts\Validation\InvokableRule  $invokable
     * @return void
     */
    protected function __construct(ValidationRule|InvokableRule $invokable)
    {
        $this->invokable = $invokable;
    }

    /**
     * Create a new implicit or explicit Invokable validation rule.
     *
     * @param  \Illuminate\Contracts\Validation\ValidationRule|\Illuminate\Contracts\Validation\InvokableRule  $invokable
     * @return \Illuminate\Contracts\Validation\Rule
     */
    public static function make($invokable)
    {
        if ($invokable->implicit ?? false) {
            return new class($invokable) extends InvokableValidationRule implements ImplicitRule {};
        }

        return new InvokableValidationRule($invokable);
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
        $this->failed = false;

        if ($this->invokable instanceof DataAwareRule) {
            $this->invokable->setData($this->validator->getData());
        }

        if ($this->invokable instanceof ValidatorAwareRule) {
            $this->invokable->setValidator($this->validator);
        }

        $method = $this->invokable instanceof ValidationRule
                        ? 'validate'
                        : '__invoke';

        $this->invokable->{$method}($attribute, $value, function ($attribute, $message = null) {
            return $this->pendingPotentiallyTranslatedString($attribute, $message);
        });

        return ! $this->failed;
    }

    /**
     * Get the underlying invokable rule.
     *
     * @return \Illuminate\Contracts\Validation\ValidationRule|\Illuminate\Contracts\Validation\InvokableRule
     */
    public function invokable()
    {
        return $this->invokable;
    }

    /**
     * Get the validation error messages.
     *
     * @return array
     */
    public function message()
    {
        return $this->messages;
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
     * Create a pending potentially translated string.
     *
     * @param  string  $attribute
     * @param  string|null  $message
     * @return \Illuminate\Translation\PotentiallyTranslatedString
     */
    protected function pendingPotentiallyTranslatedString($attribute, $message)
    {
        $destructor = $message === null
            ? fn ($message) => $this->messages[] = $message
            : fn ($message) => $this->messages[$attribute] = $message;

        $onFailure = fn () => $this->failed = true;

        return new class($message ?? $attribute, $this->validator->getTranslator(), $destructor, $onFailure) extends PotentiallyTranslatedString
        {
            /**
             * Indicates if the validation callback failed.
             *
             * @var bool
             */
            public $failed = true;

            /**
             * The callback to call when the object destructs.
             *
             * @var \Closure
             */
            protected $destructor;

            /**
             * The callback to call when rule should fail.
             *
             * @var \Closure
             */
            protected $onFailure;

            /**
             * Create a new pending potentially translated string.
             *
             * @param  string  $message
             * @param  \Illuminate\Contracts\Translation\Translator  $translator
             * @param  \Closure  $destructor
             * @return void
             */
            public function __construct($message, $translator, $destructor, $onFailure)
            {
                parent::__construct($message, $translator);

                $this->destructor = $destructor;
                $this->onFailure = $onFailure;
            }

            /**
             * Fail the rule and add message to errors if the given "value" is (or resolves to) truthy.
             *
             * @var self
             */
            public function when($failed)
            {
                $this->failed = value($failed);

                return $this;
            }

            /**
             * Fail the rule and add message to errors if the given "value" is (or resolves to) false.
             *
             * @var self
             */
            public function unless($failed)
            {
                $this->failed = ! value($failed);

                return $this;
            }

            /**
             * Handle the object's destruction.
             *
             * @return void
             */
            public function __destruct()
            {
                if (! $this->failed) {
                    return;
                }

                ($this->onFailure)();

                ($this->destructor)($this->toString());
            }
        };
    }
}
