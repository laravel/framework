<?php

namespace Illuminate\Validation;

use Illuminate\Contracts\Validation\Rule as RuleContract;
use Illuminate\Contracts\Validation\ValidatorAwareRule;
use Illuminate\Translation\CreatesPotentiallyTranslatedStrings;
use Illuminate\Validation\Concerns\HasValidator;

class ClosureValidationRule implements RuleContract, ValidatorAwareRule
{
    use CreatesPotentiallyTranslatedStrings, HasValidator;

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
            $this->failed = true;

            return $this->pendingPotentiallyTranslatedString($attribute, $message);
        }, $this->validator);

        return ! $this->failed;
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
}
