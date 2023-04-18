<?php

namespace Illuminate\Validation;

use Illuminate\Contracts\Validation\Rule as RuleContract;
use Illuminate\Contracts\Validation\ValidatorAwareRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class ClosureValidationRule implements RuleContract, ValidatorAwareRule
{
    /**
     * The callback that validates the attribute.
     *
     * @var \Closure
     */
    public $callback;

    /**
     * Indicates if the validation callback failed.
     *
     * @var bool
     */
    public $failed = false;

    /**
     * The validation error messages.
     *
     * @var array
     */
    public $messages = [];

    /**
     * The current validator.
     *
     * @var \Illuminate\Validation\Validator
     */
    protected $validator;

    /**
     * Create a new Closure based validation rule.
     *
     * @param  \Closure  $callback
     * @return void
     */
    public function __construct($callback)
    {
        $this->callback = $callback;
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

        $this->callback->__invoke($attribute, $value, function ($attribute, $message = null) {
            return $this->pendingPotentiallyTranslatedString($attribute, $message);
        });

        return ! $this->failed;
    }

    /**
     * Get the validation error messages.
     *
     * @return string
     */
    public function message()
    {
        return $this->messages;
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
     * @param  ?string  $message
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
             * @param  \Closure  $onFailure
             * @return void
             */
            public function __construct($message, $translator, $destructor, $onFailure)
            {
                parent::__construct($message, $translator);

                $this->destructor = $destructor;
                $this->onFailure = $onFailure;
            }

            /**
             * Raise the error message if the given condition is true.
             *
             * @param  mixed  $failed
             * @return $this
             */
            public function when($failed)
            {
                $this->failed = value($failed);

                return $this;
            }

            /**
             * Raise the error message unless the given condition is true.
             *
             * @param  mixed  $failed
             * @return $this
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
