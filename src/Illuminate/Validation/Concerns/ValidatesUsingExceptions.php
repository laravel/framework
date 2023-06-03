<?php

namespace Illuminate\Validation\Concerns;

use Closure;
use Illuminate\Validation\ValidationRuleFailException;
use Illuminate\Validation\ValidationRulePassException;

trait ValidatesUsingExceptions
{
    /**
     * Run the validation rule.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            $this->run($attribute, $value);
        } catch (ValidationRulePassException) {
            return;
        } catch (ValidationRuleFailException $exception) {
            $fail($exception->getMessage());
        }
    }

    /**
     * Pass this validation rule. Stop validating and abort immediately.
     *
     * @throws ValidationRulePassException
     */
    public function pass(): never
    {
        throw new ValidationRulePassException();
    }

    /**
     * Fail this validation rule. Stop validating and abort immediately.
     *
     * @throws ValidationRuleFailException
     */
    public function fail(string $message): never
    {
        throw new ValidationRuleFailException($message);
    }
}
